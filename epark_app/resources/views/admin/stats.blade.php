<x-app-layout>
    <div class="booking-page">
        <div class="booking-heading">
            <div>
                <h1>Statistiques</h1>
                <p>Occupation, volume de réservations et classement des places.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="booking-secondary">Vue d’ensemble</a>
                <a href="{{ route('admin.stats') }}" aria-current="page" class="booking-primary">Statistiques</a>
                <a href="{{ route('admin.stats.export', request()->query()) }}" class="booking-secondary">Exporter CSV</a>
            </div>
        </div>

        <section class="ep-card p-5 mb-6">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="stats-year" class="block text-xs font-semibold text-slate-600 mb-1.5">Année</label>
                    <select id="stats-year" name="year" class="ep-input">
                        @foreach($yearOptions as $yearOption)
                            <option value="{{ $yearOption }}" @selected($selectedYear === $yearOption)>{{ $yearOption }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="stats-site" class="block text-xs font-semibold text-slate-600 mb-1.5">Site</label>
                    <select id="stats-site" name="site_id" class="ep-input">
                        <option value="">Tous les sites</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}" @selected((string) $selectedSiteId === (string) $site->id)>{{ $site->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="stats-status" class="block text-xs font-semibold text-slate-600 mb-1.5">Statut</label>
                    <select id="stats-status" name="status" class="ep-input">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" @selected($selectedStatus === 'en_attente')>En attente</option>
                        <option value="confirmée" @selected($selectedStatus === 'confirmée')>Confirmée</option>
                        <option value="annulée" @selected($selectedStatus === 'annulée')>Annulée</option>
                        <option value="paid" @selected($selectedStatus === 'paid')>Payée</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="booking-primary w-full">Filtrer</button>
                </div>
            </form>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="ep-card p-5">
                <p class="text-xs font-semibold text-slate-600 uppercase">Réservations</p>
                <p class="text-2xl font-bold mt-2">{{ $totalReservations }}</p>
                <p class="text-xs text-slate-500 mt-1">Période {{ $start->format('d.m.Y') }} – {{ $end->format('d.m.Y') }}</p>
            </div>
            <div class="ep-card p-5">
                <p class="text-xs font-semibold text-slate-600 uppercase">Montants déclarés (payés)</p>
                <p class="text-2xl font-bold mt-2">{{ format_chf($totalRevenueCents / 100) }}</p>
                <p class="text-xs text-slate-500 mt-1">Selon les filtres appliqués</p>
            </div>
            <div class="ep-card p-5">
                <p class="text-xs font-semibold text-slate-600 uppercase">Taux d’occupation</p>
                <p class="text-2xl font-bold mt-2">{{ $occupancyRate }}%</p>
                <p class="text-xs text-slate-500 mt-1">{{ $reservedHours }} heures réservées</p>
            </div>
        </div>

        <section class="ep-card overflow-hidden">
            <div class="px-5 py-4 border-b border-[#dce3ed]">
                <h2 class="text-base font-bold">Top places</h2>
                <p class="text-xs text-slate-500 mt-0.5">Classement par nombre de réservations</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-[#f8fafc] border-b border-[#dce3ed]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Place</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Site</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Réservations</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600">Montant total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#dce3ed]">
                        @forelse($topPlaces as $row)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ optional($row->place)->nom ?? 'Place archivée' }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ optional(optional($row->place)->site)->nom ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $row->reservations_count }}</td>
                                <td class="px-4 py-3 font-semibold">{{ format_chf(($row->revenue_cents ?? 0) / 100) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Aucune place pour cette période.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
