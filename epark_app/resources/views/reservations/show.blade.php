<x-app-layout>
    @php
        $user = auth()->user();
        $isTenant = $user && $user->id === $reservation->user_id;
        $owner = $reservation->place?->user ?? $reservation->place?->site?->user;
        $isOwner = $user && (
            $user->id === $reservation->place?->user_id
            || ($reservation->place?->user_id === null && $user->id === $reservation->place?->site?->user_id)
        );
        $isUpcoming = $reservation->date_fin->isFuture();
        $isCompleted = $reservation->statut === 'confirmée' && $reservation->date_fin->isPast();
        $hasFeedback = $reservation->feedback !== null;
        $hasOwnerPaymentInfo = $owner && (! empty($owner->twint_phone) || ! empty($owner->iban) || ! empty($owner->payment_instructions));

        $statusLabel = match (true) {
            $reservation->statut === 'en_attente' => 'En attente du propriétaire',
            $reservation->statut === 'confirmée' && $isCompleted => 'Terminée',
            $reservation->statut === 'confirmée' => 'Confirmée',
            $reservation->statut === 'annulée' => 'Annulée',
            default => ucfirst(str_replace('_', ' ', $reservation->statut)),
        };

        $statusClass = match (true) {
            $reservation->statut === 'confirmée' => 'is-confirmed',
            $reservation->statut === 'annulée' => 'is-cancelled',
            default => '',
        };

        $paymentStatus = $reservation->payment_status ?? 'pending';
        $paymentLabel = match (true) {
            $reservation->isPaymentReceived() => 'Réception confirmée',
            $paymentStatus === 'paid' && ($reservation->amount_cents ?? 0) === 0 => 'Gratuit',
            $paymentStatus === 'paid' => 'Paiement déclaré',
            $paymentStatus === 'refunded' => 'Remboursement manuel à vérifier',
            $paymentStatus === 'failed' => 'Échec du paiement',
            default => 'Règlement en attente',
        };
        $paymentPillClass = match (true) {
            $reservation->isPaymentReceived() || $paymentStatus === 'paid' => 'is-confirmed',
            in_array($paymentStatus, ['refunded', 'failed'], true) => 'is-cancelled',
            default => '',
        };

        $durationMinutes = $reservation->date_debut->diffInMinutes($reservation->date_fin);
        $durationHours = intdiv($durationMinutes, 60);
        $durationRem = $durationMinutes % 60;
        $durationLabel = $durationRem === 0
            ? $durationHours.' h'
            : ($durationHours > 0 ? $durationHours.' h '.sprintf('%02d', $durationRem) : $durationMinutes.' min');

        $mapsUrl = $reservation->place?->site?->mapsUrl();
    @endphp

    <div class="booking-page">
        <a href="{{ route('reservations.index') }}" class="ep-back-link">
            <span aria-hidden="true">←</span> Retour aux réservations
        </a>

        <div class="booking-heading">
            <div>
                <div class="flex flex-wrap items-center gap-3 mb-2">
                    <span class="status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                    <span class="text-xs font-semibold text-slate-500">Réservation #{{ $reservation->id }}</span>
                </div>
                <h1>{{ $reservation->place?->nom ?? 'Place archivée' }} · {{ $reservation->place?->site?->nom ?? 'Site' }}</h1>
                <p>
                    {{ $reservation->date_debut->locale('fr')->isoFormat('dddd D MMMM YYYY') }},
                    {{ $reservation->date_debut->format('H:i') }}–{{ $reservation->date_fin->format('H:i') }}
                    ({{ $durationLabel }})
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('reservations.ics', $reservation) }}" class="booking-secondary">Calendrier (.ics)</a>
                @if($mapsUrl)
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="booking-secondary">Itinéraire</a>
                @endif
            </div>
        </div>

        @if($errors->any())
            <div class="booking-error" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Actions propriétaire directement sur la demande en attente --}}
        @if($isOwner && $reservation->statut === 'en_attente' && $isUpcoming)
            <section class="ep-card p-6 mb-6" aria-labelledby="owner-actions-heading">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                    <div>
                        <h2 id="owner-actions-heading" class="text-lg font-bold">Répondre à cette demande</h2>
                        <p class="text-sm text-slate-600 mt-1">
                            @if($paymentStatus === 'paid')
                                Le règlement est enregistré. Vous pouvez confirmer ou refuser cette réservation.
                            @else
                                Le locataire n’a pas encore déclaré son règlement. Vous pouvez confirmer sa réception dès qu’il vous a payé, ou refuser la demande.
                            @endif
                        </p>
                    </div>
                    <span class="status-pill {{ $paymentPillClass }}">{{ $paymentLabel }}</span>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @if($paymentStatus === 'paid')
                        <form method="POST" action="{{ route('reservations.valider', $reservation) }}" class="space-y-3 p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed]">
                            @csrf
                            <div>
                                <label for="confirm_owner_message" class="block text-sm font-semibold mb-1.5">Message au locataire (optionnel)</label>
                                <textarea id="confirm_owner_message" name="owner_message" rows="2" maxlength="500" placeholder="Ex. : Accès par la barrière sud, badge à l’accueil…" class="ep-input">{{ old('owner_message') }}</textarea>
                            </div>
                            <button type="submit" class="booking-primary w-full">Confirmer la réservation</button>
                        </form>
                    @else
                        <div class="p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed] flex flex-col justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">Confirmation en attente du règlement</p>
                                <p class="text-sm text-slate-600 mt-1">Dès que vous avez reçu le montant, marquez-le comme reçu pour débloquer la validation.</p>
                            </div>
                            <form method="POST" action="{{ route('reservations.confirm-payment', $reservation) }}">
                                @csrf
                                <button type="submit" class="booking-secondary w-full">Marquer le règlement comme reçu</button>
                            </form>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('reservations.refuser', $reservation) }}" onsubmit="return confirm('Refuser cette réservation ?');" class="space-y-3 p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed]">
                        @csrf
                        <div>
                            <label for="refuse_owner_message" class="block text-sm font-semibold mb-1.5">Motif du refus (optionnel)</label>
                            <textarea id="refuse_owner_message" name="owner_message" rows="2" maxlength="500" placeholder="Ex. : Place temporairement indisponible ce jour-là…" class="ep-input"></textarea>
                        </div>
                        <button type="submit" class="booking-danger w-full">Refuser la demande</button>
                    </form>
                </div>
            </section>
        @endif

        {{-- Actions locataire --}}
        @if($isTenant && ($reservation->statut !== 'annulée'))
            @php
                $canUpdate = $user->can('update', $reservation);
                $canCancel = $user->can('cancel', $reservation);
                $canDeclarePayment = $paymentStatus === 'pending' && $isUpcoming;
            @endphp
            @if($canDeclarePayment || $canUpdate || $canCancel)
                <section class="ep-card p-5 mb-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm text-slate-600">
                        @if($canDeclarePayment)
                            <p class="font-semibold text-[#18263a]">Prochaine étape : régler le propriétaire</p>
                            <p class="mt-0.5">Une fois le règlement effectué (TWINT, virement ou espèces), déclarez-le ici pour permettre la confirmation.</p>
                        @elseif($reservation->statut === 'en_attente')
                            <p class="font-semibold text-[#18263a]">En attente de la confirmation du propriétaire</p>
                            <p class="mt-0.5">Délai d’annulation pour cette place : {{ (int) ($reservation->place?->cancel_deadline_hours ?? 24) }} h avant le début.</p>
                        @else
                            <p class="font-semibold text-[#18263a]">Votre réservation est confirmée</p>
                            <p class="mt-0.5">Annulation possible jusqu’à {{ (int) ($reservation->place?->cancel_deadline_hours ?? 24) }} h avant le début.</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($canDeclarePayment)
                            <form method="POST" action="{{ route('reservations.payer', $reservation->id) }}">
                                @csrf
                                <button type="submit" class="booking-primary">Déclarer mon paiement</button>
                            </form>
                        @endif
                        @if($canUpdate)
                            <a href="{{ route('reservations.edit', $reservation) }}" class="booking-secondary">Modifier</a>
                        @endif
                        @if($canCancel)
                            <form method="POST" action="{{ route('reservations.destroy', $reservation) }}" onsubmit="return confirm('Annuler cette réservation ?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="booking-danger">Annuler</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endif
        @endif

        <div class="booking-grid">
            <div class="space-y-6">
                {{-- Message du propriétaire --}}
                @if($reservation->owner_message)
                    <section class="ep-card p-6">
                        <h2 class="text-lg font-bold mb-3">Message du propriétaire</h2>
                        <p class="text-sm leading-relaxed text-[#18263a] bg-[#f3f6fa] border border-[#dce3ed] rounded-lg p-4">{{ $reservation->owner_message }}</p>
                    </section>
                @endif

                {{-- Détails place & horaire --}}
                <section class="ep-card p-6">
                    <h2 class="text-lg font-bold mb-4">Détails de la réservation</h2>
                    <dl class="pass-details !mt-0">
                        <div>
                            <dt>Place</dt>
                            <dd>{{ $reservation->place?->nom ?? 'Place archivée' }}</dd>
                        </div>
                        <div>
                            <dt>Parking / Site</dt>
                            <dd>{{ $reservation->place?->site?->nom ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt>Adresse</dt>
                            <dd>{{ $reservation->place?->site?->adresse ?? 'Adresse non renseignée' }}</dd>
                        </div>
                        <div>
                            <dt>Durée</dt>
                            <dd>{{ $durationLabel }}@if($reservation->battement_minutes) <span class="text-xs font-normal text-slate-500">(+{{ $reservation->battement_minutes }} min de battement)</span>@endif</dd>
                        </div>
                        <div>
                            <dt>Début</dt>
                            <dd>{{ $reservation->date_debut->format('d.m.Y à H:i') }}</dd>
                        </div>
                        <div>
                            <dt>Fin</dt>
                            <dd>{{ $reservation->date_fin->format('d.m.Y à H:i') }}</dd>
                        </div>
                        @if($reservation->vehicle_plate)
                            <div>
                                <dt>Plaque d’immatriculation</dt>
                                <dd><span class="status-pill is-neutral">{{ $reservation->vehicle_plate }}</span></dd>
                            </div>
                        @endif
                    </dl>
                </section>

                {{-- Avis après réservation terminée --}}
                @if($isTenant && $isCompleted)
                    <section class="ep-card p-6">
                        <h2 class="text-lg font-bold mb-3">Votre avis</h2>
                        @if($hasFeedback)
                            <div class="p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed]">
                                <div class="flex items-center gap-1 mb-2" aria-label="Note {{ $reservation->feedback->rating }} sur 5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="text-lg {{ $i <= $reservation->feedback->rating ? 'text-amber-500' : 'text-slate-300' }}" aria-hidden="true">★</span>
                                    @endfor
                                    <span class="ml-2 text-sm font-semibold text-slate-700">{{ $reservation->feedback->rating }}/5</span>
                                </div>
                                @if($reservation->feedback->comment)
                                    <p class="text-sm text-[#18263a]">{{ $reservation->feedback->comment }}</p>
                                @endif
                                <p class="text-xs text-slate-500 mt-2">Avis déposé le {{ $reservation->feedback->created_at->format('d.m.Y') }}</p>
                            </div>
                        @else
                            <p class="text-sm text-slate-600 mb-4">Votre réservation est terminée. Partagez votre expérience pour aider les prochains utilisateurs.</p>
                            <a href="{{ route('reservations.feedback.create', $reservation) }}" class="booking-primary">Donner mon avis</a>
                        @endif
                    </section>
                @endif
            </div>

            {{-- Colonne droite : règlement, client, dépassement --}}
            <aside class="space-y-6">
                <section class="ep-card p-6" x-data="{ copiedField: '' }">
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <h2 class="text-lg font-bold">Règlement</h2>
                        <span class="status-pill {{ $paymentPillClass }}">{{ $paymentLabel }}</span>
                    </div>
                    <div class="summary-total !border-t-0 !pt-0">
                        <span>Montant total</span>
                        <strong>{{ format_chf(($reservation->amount_cents ?? 0) / 100) }}</strong>
                    </div>

                    @if($reservation->isPaymentReceived())
                        <p class="text-xs font-semibold text-[#166534] mt-3">
                            Réception confirmée par le propriétaire le {{ $reservation->payment_received_at->format('d.m.Y à H:i') }}.
                        </p>
                    @endif

                    @if(($reservation->amount_cents ?? 0) > 0 && $hasOwnerPaymentInfo)
                        <div class="mt-4 pt-4 border-t border-[#dce3ed] space-y-2.5 text-sm">
                            <p class="text-xs font-bold uppercase text-slate-500">Coordonnées du propriétaire</p>
                            @if(! empty($owner->twint_phone))
                                <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg bg-[#f3f6fa]">
                                    <div>
                                        <span class="block text-[11px] text-slate-500">TWINT</span>
                                        <strong class="text-sm">{{ $owner->twint_phone }}</strong>
                                    </div>
                                    <button type="button" @click="navigator.clipboard?.writeText(@js($owner->twint_phone)); copiedField='twint'" class="text-link text-xs" x-text="copiedField === 'twint' ? 'Copié' : 'Copier'"></button>
                                </div>
                            @endif
                            @if(! empty($owner->iban))
                                <div class="flex items-center justify-between gap-2 p-2.5 rounded-lg bg-[#f3f6fa]">
                                    <div class="min-w-0">
                                        <span class="block text-[11px] text-slate-500">IBAN</span>
                                        <strong class="text-xs font-mono break-all">{{ $owner->iban }}</strong>
                                    </div>
                                    <button type="button" @click="navigator.clipboard?.writeText(@js($owner->iban)); copiedField='iban'" class="text-link text-xs shrink-0" x-text="copiedField === 'iban' ? 'Copié' : 'Copier'"></button>
                                </div>
                            @endif
                            <p class="text-xs text-slate-600">Référence recommandée : <strong>ePark #{{ $reservation->id }}</strong></p>
                            @if(! empty($owner->payment_instructions))
                                <p class="text-xs text-slate-600">{{ $owner->payment_instructions }}</p>
                            @endif
                        </div>
                    @endif

                    @if($isOwner && ! $reservation->isPaymentReceived() && $reservation->statut !== 'annulée' && ($reservation->amount_cents ?? 0) > 0)
                        <form method="POST" action="{{ route('reservations.confirm-payment', $reservation) }}" class="mt-4 pt-4 border-t border-[#dce3ed]">
                            @csrf
                            <button type="submit" class="booking-secondary w-full">Confirmer la réception du règlement</button>
                        </form>
                    @endif

                    <p class="summary-note !mb-0">
                        Règlement manuel auprès du propriétaire. Aucun moyen de paiement n’est débité en ligne.
                    </p>
                </section>

                <section class="ep-card p-6">
                    <h2 class="text-lg font-bold mb-3">Locataire</h2>
                    <p class="font-semibold text-sm">{{ $reservation->user?->name ?? 'N/A' }}</p>
                    <p class="text-sm text-slate-600 mt-1">{{ $reservation->user?->email ?? 'N/A' }}</p>
                    @if($reservation->vehicle_plate)
                        <p class="text-xs text-slate-600 mt-2">Plaque : <strong>{{ $reservation->vehicle_plate }}</strong></p>
                    @endif
                </section>

                <section class="ep-card p-6">
                    <h2 class="text-lg font-bold mb-3">Pénalités de dépassement</h2>
                    <p class="text-xs text-slate-600 mb-3">Règles applicables : +1 h : 40 CHF · +3 h : 80 CHF · +1 jour : 120 CHF.</p>
                    @if($reservation->overstay_minutes > 0)
                        <div class="booking-error !m-0">
                            <p class="font-bold">Dépassement constaté : {{ $reservation->overstay_minutes }} min</p>
                            <p class="mt-1">Pénalité appliquée : {{ format_chf(($reservation->penalty_cents ?? 0) / 100) }}</p>
                            @if($reservation->actual_end_at)
                                <p class="text-xs mt-1">Départ constaté : {{ $reservation->actual_end_at->format('d.m.Y à H:i') }}</p>
                            @endif
                        </div>
                    @else
                        <span class="status-pill is-confirmed">Aucun dépassement enregistré</span>
                    @endif

                    @if($isOwner && $reservation->statut === 'confirmée' && $reservation->date_debut->isPast())
                        <details class="mt-4 pt-4 border-t border-[#dce3ed]">
                            <summary class="cursor-pointer text-sm font-semibold text-[#4338ca]">Signaler un départ tardif</summary>
                            <form method="POST" action="{{ route('reservations.overstay', $reservation) }}" class="mt-3 space-y-3">
                                @csrf
                                <div>
                                    <label for="actual_end_at" class="block text-xs font-semibold text-slate-600 mb-1">Heure de libération constatée</label>
                                    <input
                                        id="actual_end_at"
                                        type="datetime-local"
                                        name="actual_end_at"
                                        min="{{ $reservation->date_fin->format('Y-m-d\TH:i') }}"
                                        value="{{ old('actual_end_at', optional($reservation->actual_end_at)->format('Y-m-d\TH:i') ?? $reservation->date_fin->copy()->addHours(2)->format('Y-m-d\TH:i')) }}"
                                        class="ep-input"
                                        required>
                                </div>
                                <button type="submit" class="booking-secondary w-full">Enregistrer le dépassement</button>
                            </form>
                        </details>
                    @endif
                </section>
            </aside>
        </div>
    </div>
</x-app-layout>
