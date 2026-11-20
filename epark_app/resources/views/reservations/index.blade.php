<x-app-layout>
    <div class="booking-page">
        <div class="booking-heading"><div><h1>Mes réservations</h1><p>Votre place, son statut et la prochaine action.</p></div><div class="flex flex-wrap gap-2"><a href="{{ route('reservations.export', request()->query()) }}" class="booking-secondary">Exporter CSV</a><a href="{{ route('express.index') }}" class="booking-primary">Réserver une place</a></div></div>
        @if($errors->any())<p class="booking-error" role="alert">{{ $errors->first() }}</p>@endif
        <form method="GET" action="{{ route('reservations.index') }}" class="flex flex-wrap gap-3 mb-6 items-end">
            <div class="flex-1 min-w-[140px]"><label for="period" class="text-xs text-slate-600 block mb-1">Période</label><select id="period" name="period" class="booking-select"><option value="upcoming" @selected(request('period', in_array(request('status'), ['annulée', 'terminee']) ? 'all' : 'upcoming') === 'upcoming')>À venir</option><option value="past" @selected(request('period') === 'past')>Passées</option><option value="all" @selected(request('period', in_array(request('status'), ['annulée', 'terminee']) ? 'all' : '') === 'all')>Toutes</option></select></div>
            <div class="flex-1 min-w-[140px]"><label for="status" class="text-xs text-slate-600 block mb-1">Statut</label><select id="status" name="status" class="booking-select"><option value="">Tous les statuts</option>@foreach(['en_attente' => 'En attente', 'confirmée' => 'Confirmées', 'annulée' => 'Annulées', 'terminee' => 'Terminées'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
            <button type="submit" class="booking-secondary">Afficher</button>
        </form>
        <div class="grid gap-4">
            @forelse($reservations as $reservation)
                @php
                    $isOwner = auth()->id() === $reservation->place?->user_id;
                    $isActive = $reservation->date_fin->isFuture() && $reservation->statut !== 'annulée';
                    $statusLabel = $reservation->statut === 'en_attente' ? 'En attente' : ($reservation->statut === 'confirmée' && ! $isActive ? 'Terminée' : ucfirst($reservation->statut));
                @endphp
                <article class="bg-white border border-slate-200 rounded-xl p-5">
                    <div class="flex flex-wrap justify-between gap-3"><div><a class="text-link" href="{{ route('reservations.show', $reservation) }}">{{ $reservation->place?->nom ?? 'Place archivée' }} · {{ $reservation->place?->site?->nom }}</a><p class="text-sm text-slate-600 mt-2">{{ $reservation->date_debut->format('d.m.Y') }}, {{ $reservation->date_debut->format('H:i') }}–{{ $reservation->date_fin->format('H:i') }}</p>@if($isOwner)<p class="text-sm text-slate-600 mt-1">{{ $reservation->user?->name }}@if($reservation->vehicle_plate) · Plaque {{ $reservation->vehicle_plate }}@endif</p>@elseif($reservation->vehicle_plate)<p class="text-xs text-slate-500 mt-1">Plaque : {{ $reservation->vehicle_plate }}</p>@endif</div><span class="status-pill self-start {{ $reservation->statut === 'confirmée' ? 'is-confirmed' : ($reservation->statut === 'annulée' ? 'is-cancelled' : '') }}">{{ $statusLabel }}</span></div>
                    <div class="flex flex-wrap items-center justify-between gap-4 mt-4 pt-4 border-t border-slate-100"><div><strong>{{ format_chf(($reservation->amount_cents ?? 0) / 100) }}</strong><p class="text-xs text-slate-600 mt-1">{{ $reservation->isPaymentReceived() ? 'Réception confirmée' : ($reservation->payment_status === 'paid' ? ($reservation->amount_cents === 0 ? 'Gratuit' : 'Paiement déclaré') : ($reservation->payment_status === 'refunded' ? 'Remboursement manuel à vérifier' : 'Règlement en attente')) }}</p></div><div class="flex flex-wrap gap-2">
                        <a href="{{ route('reservations.show', $reservation) }}" class="booking-secondary">Détails</a>
                        @if($isActive && $reservation->statut === 'en_attente')
                            @if($isOwner && $reservation->payment_status === 'paid')<form method="POST" action="{{ route('reservations.valider', $reservation) }}">@csrf<button class="booking-primary" type="submit">Confirmer</button></form>@endif
                            @if($isOwner)<form method="POST" action="{{ route('reservations.refuser', $reservation) }}" onsubmit="return confirm('Refuser cette réservation ?');">@csrf<button class="booking-secondary" type="submit">Refuser</button></form>@endif
                            @can('update', $reservation)<a href="{{ route('reservations.edit', $reservation) }}" class="booking-secondary">Modifier</a>@endcan
                        @endif
                    </div></div>
                </article>
            @empty
                <div class="booking-empty"><h2 class="text-lg font-bold">Aucune réservation dans cette vue</h2><p>Retrouvez toutes vos réservations ou choisissez une nouvelle place.</p><div class="flex flex-wrap gap-3 justify-center mt-4"><a href="{{ route('reservations.index', ['period' => 'all']) }}" class="booking-secondary">Toutes mes réservations</a><a href="{{ route('express.index') }}" class="booking-primary">Trouver une place</a></div></div>
            @endforelse
        </div>
        <div class="mt-6">{{ $reservations->links() }}</div>
    </div>
</x-app-layout>
