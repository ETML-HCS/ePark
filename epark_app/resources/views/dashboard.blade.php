<x-app-layout>
    <div class="booking-page">
        <div class="booking-heading">
            <div><h1>Mon activité</h1><p>Bonjour {{ explode(' ', auth()->user()->name)[0] }}. Retrouvez ici les actions à suivre.</p></div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('places.create') }}" class="booking-secondary">Proposer une place</a>
                <a href="{{ route('express.index') }}" class="booking-primary">Réserver une place</a>
            </div>
        </div>
        @if($errors->any())<div role="alert" class="booking-error">{{ $errors->first() }}</div>@endif
        @if($isOwner)
            <section class="mb-8 bg-white border border-slate-200 rounded-xl p-5">
                <div class="section-heading"><h2 class="text-xl font-bold">Demandes à traiter</h2><a href="{{ route('reservations.index', ['status' => 'en_attente']) }}" class="text-link text-sm">Toutes les demandes</a></div>
                @forelse($pendingRequests as $reservation)
                    <div class="py-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div><a href="{{ route('reservations.show', $reservation) }}" class="font-bold text-link">{{ $reservation->place?->nom }} · {{ $reservation->user?->name }}</a><p class="text-sm text-slate-600 mt-1">{{ $reservation->date_debut->format('d.m.Y H:i') }}–{{ $reservation->date_fin->format('H:i') }} · {{ format_chf($reservation->amount_cents / 100) }}@if($reservation->vehicle_plate) · {{ $reservation->vehicle_plate }}@endif</p></div>
                        <div class="flex flex-wrap gap-2">
                            @if($reservation->payment_status === 'paid')
                                <form method="POST" action="{{ route('reservations.valider', $reservation) }}">@csrf<button type="submit" class="booking-primary">Confirmer</button></form>
                            @else
                                <a href="{{ route('reservations.show', $reservation) }}" class="booking-secondary">Voir le règlement</a>
                            @endif
                            <form method="POST" action="{{ route('reservations.refuser', $reservation) }}" onsubmit="return confirm('Refuser cette demande ?');">@csrf<button type="submit" class="booking-secondary">Refuser</button></form>
                        </div>
                    </div>
                @empty
                    <p class="text-slate-600 text-sm">Aucune demande en attente. <a href="{{ route('places.mes') }}" class="text-link">Gérer mes places</a></p>
                @endforelse
            </section>
            <div class="grid grid-cols-3 gap-3 mb-8">
                <a href="{{ route('places.mes') }}" class="bg-white border border-slate-200 rounded-xl p-4"><span class="text-sm text-slate-600">Mes places</span><strong class="block text-2xl mt-2">{{ $nbPlaces }}</strong></a>
                <a href="{{ route('reservations.index', ['status' => 'en_attente']) }}" class="bg-white border border-slate-200 rounded-xl p-4"><span class="text-sm text-slate-600">En attente</span><strong class="block text-2xl mt-2">{{ $nbReservationsAttente }}</strong></a>
                <div class="bg-white border border-slate-200 rounded-xl p-4"><span class="text-sm text-slate-600">Montant confirmé ce mois</span><strong class="block text-lg mt-2">{{ format_chf($revenusMois) }}</strong></div>
            </div>
            <div class="flex flex-wrap gap-3 mb-8"><a href="{{ route('places.create') }}" class="booking-secondary">Ajouter une place</a><a href="{{ route('sites.index') }}" class="booking-secondary">Mes sites</a></div>
        @else
            <section class="ep-card p-5 mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold">Vous avez une place de parc disponible ?</h2>
                    <p class="text-sm text-slate-600 mt-0.5">Proposez votre place sur vos créneaux libres et gérez vos disponibilités depuis « Mes places ».</p>
                </div>
                <a href="{{ route('places.create') }}" class="booking-secondary shrink-0">Proposer ma place</a>
            </section>
        @endif
        <section class="upcoming-bookings"><div class="section-heading"><h2>Dernières réservations</h2><a href="{{ route('reservations.index', ['period' => 'all']) }}" class="text-link">Tout voir</a></div>
            @forelse($reservations as $reservation)
                <a href="{{ route('reservations.show', $reservation) }}" class="upcoming-row"><div><strong>{{ $reservation->place?->nom ?? 'Place archivée' }} · {{ $reservation->place?->site?->nom }}</strong><p>{{ $reservation->date_debut->format('d.m.Y H:i') }}–{{ $reservation->date_fin->format('H:i') }}@if($isOwner) · {{ $reservation->user?->name }}@endif</p></div><span class="status-pill {{ $reservation->statut === 'confirmée' ? 'is-confirmed' : ($reservation->statut === 'annulée' ? 'is-cancelled' : '') }}">{{ $reservation->statut === 'en_attente' ? 'En attente' : ($reservation->statut === 'confirmée' && $reservation->date_fin->isPast() ? 'Terminée' : ucfirst($reservation->statut)) }}</span></a>
            @empty
                <div class="booking-empty"><h3>Aucune réservation pour le moment</h3><p>Choisissez une date et une place pour commencer.</p><a href="{{ route('express.index') }}" class="booking-primary mt-4">Trouver une place</a></div>
            @endforelse
        </section>
    </div>
</x-app-layout>
