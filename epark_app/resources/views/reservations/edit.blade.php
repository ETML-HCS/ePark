<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('reservations.show', $reservation) }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-all shadow-sm">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900">Modifier la reservation</h2>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white min-h-screen">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-xl p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-bold text-red-900 mb-2">Erreurs detectees</h3>
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700">• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-8 text-white">
                    <h3 class="text-2xl font-black mb-1">Reservation #{{ $reservation->id }}</h3>
                    <p class="text-indigo-100">Place : {{ optional($place)->nom ?? 'N/A' }}</p>
                </div>

                <form method="POST" action="{{ route('reservations.update', $reservation) }}" class="p-8 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="date" class="text-sm font-bold text-gray-900 mb-2 block">Date</label>
                        <input
                            type="date"
                            id="date"
                            name="date"
                            min="{{ $minDate }}"
                            max="{{ $maxDate }}"
                            value="{{ old('date', $selectedDate) }}"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-gray-900"
                            required>
                    </div>

                    <div>
                        <label for="segment" class="text-sm font-bold text-gray-900 mb-2 block">Segment</label>
                        <select
                            id="segment"
                            name="segment"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-gray-900"
                            required>
                            <option value="matin_travail" {{ old('segment', $selectedSegment) === 'matin_travail' ? 'selected' : '' }}>Matin (08:00 - 12:00)</option>
                            <option value="aprem_travail" {{ old('segment', $selectedSegment) === 'aprem_travail' ? 'selected' : '' }}>Apres-midi (12:00 - 17:30)</option>
                            <option value="soir" {{ old('segment', $selectedSegment) === 'soir' ? 'selected' : '' }}>Soir (18:00 - 24:00)</option>
                            <option value="nuit" {{ old('segment', $selectedSegment) === 'nuit' ? 'selected' : '' }}>Nuit (00:00 - 07:30)</option>
                        </select>
                        <p class="mt-2 text-xs text-gray-500">Le delai d'annulation pour cette place est de {{ (int) ($place->cancel_deadline_hours ?? 24) }}h.</p>
                    </div>

                    <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('reservations.show', $reservation) }}" class="flex-1 text-center py-3 bg-gray-100 text-gray-700 rounded-xl font-bold hover:bg-gray-200 transition-all">
                            Annuler
                        </a>
                        <button type="submit" class="flex-1 flex items-center justify-center gap-2 py-3 bg-indigo-600 text-white rounded-xl font-bold hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
