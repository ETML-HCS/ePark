<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Back-office</h2>
    </x-slot>

    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Utilisateurs</p>
                <p class="text-2xl font-bold">{{ $stats['users'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Sites</p>
                <p class="text-2xl font-bold">{{ $stats['sites'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Places</p>
                <p class="text-2xl font-bold">{{ $stats['places'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Réservations</p>
                <p class="text-2xl font-bold">{{ $stats['reservations'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Paiements</p>
                <p class="text-2xl font-bold">{{ $stats['payments'] }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Revenus (payés)</p>
                <p class="text-2xl font-bold">{{ format_chf($stats['revenue']) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold">Dernières réservations</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Place</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($recentReservations as $reservation)
                                <tr>
                                    <td class="px-4 py-3 text-sm">#{{ $reservation->id }}</td>
                                    <td class="px-4 py-3 text-sm">{{ optional($reservation->user)->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ optional($reservation->place)->nom ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $reservation->statut }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Aucune réservation.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-semibold">Derniers paiements</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Client</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Montant</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse($recentPayments as $payment)
                                <tr>
                                    <td class="px-4 py-3 text-sm">#{{ $payment->id }}</td>
                                    <td class="px-4 py-3 text-sm">{{ optional(optional($payment->reservation)->user)->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ format_chf(($payment->amount_cents ?? 0) / 100) }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $payment->provider_status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Aucun paiement.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold">Derniers utilisateurs</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Nom</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Email</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Rôle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($recentUsers as $user)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $user->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-sm">{{ $user->role }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Aucun utilisateur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
