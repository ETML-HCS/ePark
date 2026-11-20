<x-app-layout>
    <div class="booking-page">
        <div class="booking-heading">
            <div>
                <h1>Administration</h1>
                <p>Vue d’ensemble de l’activité de la plateforme ePark.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" aria-current="page" class="booking-primary">Vue d’ensemble</a>
                <a href="{{ route('admin.stats') }}" class="booking-secondary">Statistiques</a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
            <div class="ep-card p-4">
                <p class="text-xs font-semibold text-slate-600 uppercase">Utilisateurs</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['users'] }}</p>
            </div>
            <div class="ep-card p-4">
                <p class="text-xs font-semibold text-slate-600 uppercase">Sites</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['sites'] }}</p>
            </div>
            <div class="ep-card p-4">
                <p class="text-xs font-semibold text-slate-600 uppercase">Places</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['places'] }}</p>
            </div>
            <div class="ep-card p-4">
                <p class="text-xs font-semibold text-slate-600 uppercase">Réservations</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['reservations'] }}</p>
            </div>
            <div class="ep-card p-4">
                <p class="text-xs font-semibold text-slate-600 uppercase">Paiements</p>
                <p class="text-2xl font-bold mt-2">{{ $stats['payments'] }}</p>
            </div>
            <div class="ep-card p-4">
                <p class="text-xs font-semibold text-slate-600 uppercase">Montants déclarés</p>
                <p class="text-lg font-bold mt-2">{{ format_chf($stats['revenue']) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <section class="ep-card overflow-hidden">
                <div class="px-5 py-4 border-b border-[#dce3ed]">
                    <h2 class="text-base font-bold">Dernières réservations</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[#f8fafc] border-b border-[#dce3ed]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Locataire</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Place</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#dce3ed]">
                            @forelse($recentReservations as $reservation)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('reservations.show', $reservation) }}" class="text-link">#{{ $reservation->id }}</a>
                                    </td>
                                    <td class="px-4 py-3">{{ optional($reservation->user)->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ optional($reservation->place)->nom ?? 'Place archivée' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="status-pill {{ $reservation->statut === 'confirmée' ? 'is-confirmed' : ($reservation->statut === 'annulée' ? 'is-cancelled' : '') }}">
                                            {{ $reservation->statut === 'en_attente' ? 'En attente' : ucfirst($reservation->statut) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Aucune réservation.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="ep-card overflow-hidden">
                <div class="px-5 py-4 border-b border-[#dce3ed]">
                    <h2 class="text-base font-bold">Derniers règlements</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[#f8fafc] border-b border-[#dce3ed]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Locataire</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Montant</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#dce3ed]">
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td class="px-4 py-3">#{{ $payment->id }}</td>
                                    <td class="px-4 py-3">{{ optional(optional($payment->reservation)->user)->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 font-semibold">{{ format_chf(($payment->amount_cents ?? 0) / 100) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="status-pill {{ $payment->provider_status === 'succeeded' ? 'is-confirmed' : ($payment->provider_status === 'refunded' ? 'is-cancelled' : '') }}">
                                            {{ $payment->provider_status }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Aucun paiement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="ep-card overflow-hidden">
            <div class="px-5 py-4 border-b border-[#dce3ed]">
                <h2 class="text-base font-bold">Derniers utilisateurs inscrits</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#f8fafc] border-b border-[#dce3ed]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Nom</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">E-mail</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Rôle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#dce3ed]">
                        @forelse($recentUsers as $user)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                                <td class="px-4 py-3"><span class="status-pill is-neutral">{{ ucfirst($user->role) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Aucun utilisateur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="ep-card overflow-hidden mt-6">
            <div class="px-5 py-4 border-b border-[#dce3ed]">
                <h2 class="text-base font-bold">Journal d’audit récent</h2>
                <p class="text-xs text-slate-500 mt-0.5">Traçabilité des actions sur les réservations, places et sites.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#f8fafc] border-b border-[#dce3ed]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Date</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Utilisateur</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Action</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Entité</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#dce3ed]">
                        @forelse(($recentAuditLogs ?? collect()) as $log)
                            <tr>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">{{ $log->created_at?->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $log->user?->name ?? 'Système' }}</td>
                                <td class="px-4 py-3"><span class="status-pill is-neutral">{{ $log->action }}</span></td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-700">{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Aucun événement enregistré.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
