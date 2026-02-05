<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Donner votre avis') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    {{-- Résumé de la réservation --}}
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-medium text-gray-900 mb-2">Votre réservation</h3>
                        <dl class="grid grid-cols-2 gap-2 text-sm">
                            <dt class="text-gray-500">Site</dt>
                            <dd class="text-gray-900">{{ $reservation->place->site->nom }}</dd>
                            <dt class="text-gray-500">Place</dt>
                            <dd class="text-gray-900">{{ $reservation->place->numero }}</dd>
                            <dt class="text-gray-500">Dates</dt>
                            <dd class="text-gray-900">
                                {{ $reservation->date_debut->format('d/m/Y') }} 
                                → {{ $reservation->date_fin->format('d/m/Y') }}
                            </dd>
                            <dt class="text-gray-500">Propriétaire</dt>
                            <dd class="text-gray-900">{{ $reservation->place->user->name }}</dd>
                        </dl>
                    </div>

                    <form action="{{ route('reservations.feedback.store', $reservation) }}" method="POST">
                        @csrf

                        {{-- Note par étoiles --}}
                        <div class="mb-6" x-data="{ rating: {{ old('rating', 0) }}, hoverRating: 0 }">
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Comment évaluez-vous cette expérience ? <span class="text-red-500">*</span>
                            </label>
                            
                            <div class="flex items-center gap-1">
                                <template x-for="star in 5" :key="star">
                                    <button 
                                        type="button"
                                        @click="rating = star"
                                        @mouseenter="hoverRating = star"
                                        @mouseleave="hoverRating = 0"
                                        class="text-3xl transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded"
                                        :class="(hoverRating || rating) >= star ? 'text-yellow-400' : 'text-gray-300'"
                                    >
                                        ★
                                    </button>
                                </template>
                                
                                <span class="ml-3 text-sm text-gray-600" x-show="rating > 0">
                                    <span x-text="rating"></span>/5
                                    <span x-show="rating === 1">- Très mauvais</span>
                                    <span x-show="rating === 2">- Mauvais</span>
                                    <span x-show="rating === 3">- Correct</span>
                                    <span x-show="rating === 4">- Bien</span>
                                    <span x-show="rating === 5">- Excellent</span>
                                </span>
                            </div>
                            
                            <input type="hidden" name="rating" x-model="rating">
                            @error('rating')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Commentaire optionnel --}}
                        <div class="mb-6">
                            <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">
                                Commentaire (optionnel)
                            </label>
                            <textarea
                                id="comment"
                                name="comment"
                                rows="4"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                placeholder="Partagez votre expérience avec ce propriétaire..."
                            >{{ old('comment') }}</textarea>
                            @error('comment')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between">
                            <a href="{{ route('reservations.show', $reservation) }}" 
                               class="text-sm text-gray-600 hover:text-gray-900">
                                ← Retour à la réservation
                            </a>
                            
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition ease-in-out duration-150">
                                Envoyer mon avis
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
