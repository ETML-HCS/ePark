<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('places.mes') }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-all shadow-sm">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-xl">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                Indisponibilités - {{ $place->nom }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-xl p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-bold text-red-900 mb-2">Erreurs détectées</h3>
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700">• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Hebdomadaires --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Indisponibilités hebdomadaires
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Les plages ci-dessous sont les heures NON réservables.</p>
                </div>
                <form method="POST" action="{{ route('places.blocked-slots.update', $place->id) }}" class="p-6">
                    @csrf
                    <div class="mb-6 flex flex-wrap gap-2">
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors" data-clear-slot data-day="all">
                            ✓ Tout laisser libre
                        </button>
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-quick-slot data-day="all" data-start="00:00" data-end="23:59">
                            Tout bloquer : journée entière
                        </button>
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-quick-slot data-day="all" data-start="07:30" data-end="17:00">
                            Tout bloquer : 07:30-17:00
                        </button>
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-quick-slot data-day="all" data-start="07:30" data-end="12:00">
                            Tout bloquer : 07:30-12:00
                        </button>
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-quick-slot data-day="all" data-start="12:00" data-end="17:30">
                            Tout bloquer : 12:00-17:30
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($days as $dayIndex => $dayLabel)
                            @php
                                $slot = $availabilityByDay->get($dayIndex);
                            @endphp
                            <div class="border-2 border-gray-200 rounded-xl p-4 hover:border-indigo-200 transition-colors">
                                <div class="font-bold text-sm text-gray-900 mb-3">{{ $dayLabel }}</div>
                                <div class="flex gap-2">
                                    <input type="time" name="availability[{{ $dayIndex }}][start]" class="flex-1 border-2 border-gray-200 bg-gray-50 rounded-xl focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all" data-role="start" data-day="{{ $dayIndex }}" value="{{ $slot->start_time ?? '' }}">
                                    <input type="time" name="availability[{{ $dayIndex }}][end]" class="flex-1 border-2 border-gray-200 bg-gray-50 rounded-xl focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all" data-role="end" data-day="{{ $dayIndex }}" value="{{ $slot->end_time ?? '' }}">
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors" data-clear-slot data-day="{{ $dayIndex }}">
                                        Libre
                                    </button>
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-quick-slot data-day="{{ $dayIndex }}" data-start="00:00" data-end="23:59">
                                        24h
                                    </button>
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-quick-slot data-day="{{ $dayIndex }}" data-start="07:30" data-end="17:00">
                                        7h30-17h
                                    </button>
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-quick-slot data-day="{{ $dayIndex }}" data-start="07:30" data-end="12:00">
                                        Matin
                                    </button>
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-quick-slot data-day="{{ $dayIndex }}" data-start="12:00" data-end="17:30">
                                        Après-midi
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">
                        <x-primary-button class="px-6 py-3">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Enregistrer les disponibilités
                        </x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Exceptionnelles --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        Indisponibilités exceptionnelles
                    </h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('places.unavailability.store', $place->id) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        @csrf
                        <div>
                            <x-input-label for="date" value="Date" />
                            <x-text-input type="date" name="date" id="date" class="mt-2 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="start_time" value="Début (optionnel)" />
                            <x-text-input type="time" name="start_time" id="start_time" class="mt-2 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="end_time" value="Fin (optionnel)" />
                            <x-text-input type="time" name="end_time" id="end_time" class="mt-2 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="reason" value="Motif" />
                            <x-text-input type="text" name="reason" id="reason" class="mt-2 block w-full" placeholder="Travaux, privé..." />
                        </div>
                        <div class="md:col-span-4">
                            <x-primary-button class="px-6 py-3">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Ajouter
                            </x-primary-button>
                        </div>
                    </form>

                    <div class="mt-8">
                        @if($unavailabilities->isEmpty())
                            <div class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-medium">Aucune indisponibilité exceptionnelle</p>
                            </div>
                        @else
                            <div class="overflow-hidden rounded-xl border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Plage</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Motif</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($unavailabilities as $exception)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $exception->date->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-600">
                                                    @if($exception->start_time && $exception->end_time)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                                            {{ $exception->start_time }} - {{ $exception->end_time }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-gray-100 text-gray-600 text-xs font-semibold">
                                                            Journée entière
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600">{{ $exception->reason ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right">
                                                    <form method="POST" action="{{ route('places.unavailability.destroy', [$place->id, $exception->id]) }}" onsubmit="return confirm('Supprimer cette indisponibilité ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button class="px-3 py-1.5 text-xs">Supprimer</x-danger-button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-quick-slot], [data-clear-slot]');
                if (!button) { return; }
                const day = button.dataset.day;
                const start = button.dataset.start;
                const end = button.dataset.end;
                if (button.hasAttribute('data-clear-slot')) {
                    if (day === 'all') {
                        document.querySelectorAll('input[data-role="start"], input[data-role="end"]').forEach(i => i.value = '');
                        return;
                    }
                    const s = document.querySelector(`input[data-role="start"][data-day="${day}"]`);
                    const e = document.querySelector(`input[data-role="end"][data-day="${day}"]`);
                    if (s) s.value = '';
                    if (e) e.value = '';
                    return;
                }
                if (day === 'all') {
                    document.querySelectorAll('input[data-role="start"]').forEach(i => i.value = start);
                    document.querySelectorAll('input[data-role="end"]').forEach(i => i.value = end);
                    return;
                }
                const s = document.querySelector(`input[data-role="start"][data-day="${day}"]`);
                const e = document.querySelector(`input[data-role="end"][data-day="${day}"]`);
                if (s) s.value = start;
                if (e) e.value = end;
            });
        </script>
    @endpush
</x-app-layout>
