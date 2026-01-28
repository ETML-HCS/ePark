<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Réserver une place</h2>
    </x-slot>

    <div class="container">
        <form method="POST" action="{{ route('reservations.store') }}">
            @csrf
            <div class="mb-3">
                <label for="place_id" class="form-label">Place</label>
                <select class="form-control" id="place_id" name="place_id" required>
                    <option value="">Sélectionner une place</option>
                    @foreach($places as $place)
                        <option value="{{ $place->id }}">{{ $place->nom ?? $place->adresse }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="{{ old('date') }}" required>
            </div>
            <div class="mb-3">
                <label for="start_hour" class="form-label">Heure de début (créneau de 1h)</label>
                <select id="start_hour" name="start_hour" class="form-control" required>
                    <option value="">Sélectionner l'heure</option>
                    @foreach($hours as $h)
                        <option value="{{ $h }}" {{ old('start_hour') == $h ? 'selected' : '' }}>{{ $h }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="battement" class="form-label">Battement (marge en minutes)</label>
                <select id="battement" name="battement" class="form-control" required>
                    <option value="0" {{ old('battement', '0') == '0' ? 'selected' : '' }}>0 minutes</option>
                    <option value="10" {{ old('battement') == '10' ? 'selected' : '' }}>10 minutes</option>
                    <option value="15" {{ old('battement') == '15' ? 'selected' : '' }}>15 minutes</option>
                    <option value="20" {{ old('battement') == '20' ? 'selected' : '' }}>20 minutes</option>
                </select>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="paiement_effectue" name="paiement_effectue">
                <label class="form-check-label" for="paiement_effectue">Paiement effectué (optionnel)</label>
            </div>
            <x-primary-button>Réserver</x-primary-button>
        </form>
    </div>
</x-app-layout>
