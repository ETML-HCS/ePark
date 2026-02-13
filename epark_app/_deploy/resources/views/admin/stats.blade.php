<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Statistiques</h2>
    </x-slot>

    <div class="space-y-8">
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Annee</label>
                    <select name="year" class="w-full rounded-lg border-gray-200">
                        @foreach($yearOptions as $yearOption)
                            <option value="{{ $yearOption }}" @selected($selectedYear === $yearOption)>{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Site</label>
                    <select name="site_id" class="w-full rounded-lg border-gray-200">
                        <option value="">Tous</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected((string) $selectedSiteId === (string) $site->id)>{{ $site->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Statut</label>
                    <select name="status" class="w-full rounded-lg border-gray-200">
                        <option value="">Tous</option>
                        <option value="en_attente" @selected($selectedStatus === 'en_attente')>En attente</option>
                        <option value="confirmée" @selected($selectedStatus === 'confirmée')>Confirmee</option>
                        <option value="annulée" @selected($selectedStatus === 'annulée')>Annulee</option>
                        <option value="paid" @selected($selectedStatus === 'paid')>Payee</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button class="w-full md:w-auto px-4 py-2 rounded-lg bg-gray-900 text-white">Filtrer</button>
                </div>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Reservations</p>
                <p class="text-2xl font-bold">{{ $totalReservations }}</p>
                <p class="text-xs text-gray-400 mt-1">Periode {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>
            </div>
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Revenus (payes)</p>
                <p class="text-2xl font-bold">{{ format_chf($totalRevenueCents / 100) }}</p>
                <p class="text-xs text-gray-400 mt-1">Apres filtre statut</p>
            </div>
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 p-5">
                <p class="text-sm text-gray-500">Taux d'occupation</p>
                <p class="text-2xl font-bold">{{ $occupancyRate }}%</p>
                <p class="text-xs text-gray-400 mt-1">{{ $reservedHours }} heures reservees</p>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold">Top places</h3>
                <p class="text-xs text-gray-500">Classement par nombre de reservations</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Place</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Site</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Reservations</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500">Revenus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($topPlaces as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ optional($row->place)->nom ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm">{{ optional(optional($row->place)->site)->nom ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-sm">{{ $row->reservations_count }}</td>
                                <td class="px-4 py-3 text-sm">{{ format_chf(($row->revenue_cents ?? 0) / 100) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Aucune place pour cette periode.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
