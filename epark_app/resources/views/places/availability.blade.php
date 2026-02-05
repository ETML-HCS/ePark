<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Disponibilités - {{ $place->nom }}</h2>
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
            <h3 class="text-lg font-semibold mb-4">Disponibilités hebdomadaires</h3>
            <form method="POST" action="{{ route('places.availability.update', $place->id) }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($days as $dayIndex => $dayLabel)
                        @php
                            $slot = $availabilityByDay->get($dayIndex);
                        @endphp
                        <div class="border rounded p-3">
                            <div class="font-medium mb-2">{{ $dayLabel }}</div>
                            <div class="flex gap-2">
                                <input type="time" name="availability[{{ $dayIndex }}][start]" class="form-control" value="{{ $slot->start_time ?? '' }}">
                                <input type="time" name="availability[{{ $dayIndex }}][end]" class="form-control" value="{{ $slot->end_time ?? '' }}">
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
            <h3 class="text-lg font-semibold mb-4">Indisponibilités (exceptions)</h3>
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
</x-app-layout>
