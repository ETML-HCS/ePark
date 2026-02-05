<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Indisponibilités - {{ $place->nom }}</h2>
    </x-slot>

    <div class="container space-y-8">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold">Indisponibilités hebdomadaires</h3>
            <p class="text-sm text-gray-500 mt-1">Les plages ci-dessous sont les heures NON reservables.</p>
            <form method="POST" action="{{ route('places.availability.update', $place->id) }}">
                @csrf
                <div class="mb-4 flex flex-wrap gap-2">
                    <button type="button" class="px-3 py-1.5 text-xs rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100" data-clear-slot data-day="all">
                        Tout laisser libre
                    </button>
                    <button type="button" class="px-3 py-1.5 text-xs rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100" data-quick-slot data-day="all" data-start="00:00" data-end="23:59">
                        Tout bloquer : journee entiere
                    </button>
                    <button type="button" class="px-3 py-1.5 text-xs rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100" data-quick-slot data-day="all" data-start="07:30" data-end="17:00">
                        Tout bloquer : 07:30-17:00
                    </button>
                    <button type="button" class="px-3 py-1.5 text-xs rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100" data-quick-slot data-day="all" data-start="07:30" data-end="12:00">
                        Tout bloquer : 07:30-12:00
                    </button>
                    <button type="button" class="px-3 py-1.5 text-xs rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100" data-quick-slot data-day="all" data-start="12:00" data-end="17:30">
                        Tout bloquer : 12:00-17:30
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($days as $dayIndex => $dayLabel)
                        @php
                            $slot = $availabilityByDay->get($dayIndex);
                        @endphp
                        <div class="border rounded p-3">
                            <div class="font-medium mb-2">{{ $dayLabel }}</div>
                            <div class="flex gap-2">
                                <input type="time" name="availability[{{ $dayIndex }}][start]" class="form-control" data-role="start" data-day="{{ $dayIndex }}" value="{{ $slot->start_time ?? '' }}">
                                <input type="time" name="availability[{{ $dayIndex }}][end]" class="form-control" data-role="end" data-day="{{ $dayIndex }}" value="{{ $slot->end_time ?? '' }}">
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <button type="button" class="px-2 py-1 text-xs rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100" data-clear-slot data-day="{{ $dayIndex }}">
                                    Laisser libre
                                </button>
                                <button type="button" class="px-2 py-1 text-xs rounded-lg bg-gray-100 hover:bg-gray-200" data-quick-slot data-day="{{ $dayIndex }}" data-start="00:00" data-end="23:59">
                                    Bloquer journee entiere
                                </button>
                                <button type="button" class="px-2 py-1 text-xs rounded-lg bg-gray-100 hover:bg-gray-200" data-quick-slot data-day="{{ $dayIndex }}" data-start="07:30" data-end="17:00">
                                    Bloquer 07:30-17:00
                                </button>
                                <button type="button" class="px-2 py-1 text-xs rounded-lg bg-gray-100 hover:bg-gray-200" data-quick-slot data-day="{{ $dayIndex }}" data-start="07:30" data-end="12:00">
                                    Bloquer 07:30-12:00
                                </button>
                                <button type="button" class="px-2 py-1 text-xs rounded-lg bg-gray-100 hover:bg-gray-200" data-quick-slot data-day="{{ $dayIndex }}" data-start="12:00" data-end="17:30">
                                    Bloquer 12:00-17:30
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4">
                    <x-primary-button>Enregistrer</x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Indisponibilités exceptionnelles</h3>
            <form method="POST" action="{{ route('places.unavailability.store', $place->id) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                @csrf
                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" required>
                </div>
                <div>
                    <label class="form-label">Début (optionnel)</label>
                    <input type="time" name="start_time" class="form-control">
                </div>
                <div>
                    <label class="form-label">Fin (optionnel)</label>
                    <input type="time" name="end_time" class="form-control">
                </div>
                <div>
                    <label class="form-label">Motif</label>
                    <input type="text" name="reason" class="form-control" placeholder="Travaux, privé...">
                </div>
                <div class="md:col-span-4">
                    <x-primary-button>Ajouter</x-primary-button>
                </div>
            </form>

            <div class="mt-6">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Plage</th>
                            <th>Motif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($unavailabilities as $exception)
                            <tr>
                                <td>{{ $exception->date->format('Y-m-d') }}</td>
                                <td>
                                    @if($exception->start_time && $exception->end_time)
                                        {{ $exception->start_time }} - {{ $exception->end_time }}
                                    @else
                                        Journée entière
                                    @endif
                                </td>
                                <td>{{ $exception->reason ?? '-' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('places.unavailability.destroy', [$place->id, $exception->id]) }}" onsubmit="return confirm('Supprimer cette indisponibilité ?');">
                                        @csrf
                                        @method('DELETE')
                                        <x-danger-button class="px-3 py-1 text-sm">Supprimer</x-danger-button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">Aucune indisponibilité.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-quick-slot], [data-clear-slot]');
                if (!button) {
                    return;
                }

                const day = button.dataset.day;
                const start = button.dataset.start;
                const end = button.dataset.end;

                if (button.hasAttribute('data-clear-slot')) {
                    if (day === 'all') {
                        document.querySelectorAll('input[data-role="start"], input[data-role="end"]').forEach((input) => {
                            input.value = '';
                        });
                        return;
                    }

                    const startInput = document.querySelector(`input[data-role="start"][data-day="${day}"]`);
                    const endInput = document.querySelector(`input[data-role="end"][data-day="${day}"]`);
                    if (startInput) {
                        startInput.value = '';
                    }
                    if (endInput) {
                        endInput.value = '';
                    }
                    return;
                }

                if (day === 'all') {
                    document.querySelectorAll('input[data-role="start"]').forEach((input) => {
                        input.value = start;
                    });
                    document.querySelectorAll('input[data-role="end"]').forEach((input) => {
                        input.value = end;
                    });
                    return;
                }

                const startInput = document.querySelector(`input[data-role="start"][data-day="${day}"]`);
                const endInput = document.querySelector(`input[data-role="end"][data-day="${day}"]`);

                if (startInput) {
                    startInput.value = start;
                }

                if (endInput) {
                    endInput.value = end;
                }
            });
        </script>
    @endpush
</x-app-layout>
