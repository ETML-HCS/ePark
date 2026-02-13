<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('reservations.show', $reservation) }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-all shadow-sm">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-xl">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </div>
                Donner votre avis
            </h2>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white min-h-screen">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

                {{-- Résumé de la réservation --}}
                <div class="p-6 bg-gray-50 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 mb-3">Votre réservation</h3>
                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        <dt class="text-gray-500">Site</dt>
                        <dd class="font-semibold text-gray-900">{{ $reservation->place->site->nom }}</dd>
                        <dt class="text-gray-500">Place</dt>
                        <dd class="font-semibold text-gray-900">{{ $reservation->place->numero }}</dd>
                        <dt class="text-gray-500">Dates</dt>
                        <dd class="font-semibold text-gray-900">
                            {{ $reservation->date_debut->format('d/m/Y') }} 
                            → {{ $reservation->date_fin->format('d/m/Y') }}
                        </dd>
                        <dt class="text-gray-500">Propriétaire</dt>
                        <dd class="font-semibold text-gray-900">{{ $reservation->place->user->name }}</dd>
                    </dl>
                </div>

                <form action="{{ route('reservations.feedback.store', $reservation) }}" method="POST" class="p-6 space-y-6">
                    @csrf

                    {{-- Note par étoiles --}}
                    <div x-data="{ rating: {{ old('rating', 0) }}, hoverRating: 0 }">
                        <x-input-label value="Comment évaluez-vous cette expérience ?" />
                        <span class="text-red-500 text-xs">* Obligatoire</span>
                        
                        <div class="flex items-center gap-1 mt-3">
                            <template x-for="star in 5" :key="star">
                                <button 
                                    type="button"
                                    @click="rating = star"
                                    @mouseenter="hoverRating = star"
                                    @mouseleave="hoverRating = 0"
                                    class="text-3xl transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-xl p-1"
                                    :class="(hoverRating || rating) >= star ? 'text-yellow-400' : 'text-gray-300'"
                                >
                                    ★
                                </button>
                            </template>
                            
                            <span class="ml-3 text-sm text-gray-600" x-show="rating > 0">
                                <span x-text="rating" class="font-bold"></span>/5
                                <span x-show="rating === 1" class="text-red-600">— Très mauvais</span>
                                <span x-show="rating === 2" class="text-orange-600">— Mauvais</span>
                                <span x-show="rating === 3" class="text-yellow-600">— Correct</span>
                                <span x-show="rating === 4" class="text-green-600">— Bien</span>
                                <span x-show="rating === 5" class="text-emerald-600">— Excellent</span>
                            </span>
                        </div>
                        
                        <input type="hidden" name="rating" x-model="rating">
                        <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                    </div>

                    {{-- Commentaire optionnel --}}
                    <div>
                        <x-input-label for="comment" value="Commentaire (optionnel)" />
                        <textarea
                            id="comment"
                            name="comment"
                            rows="4"
                            class="mt-2 w-full border-2 border-gray-200 bg-gray-50 rounded-xl focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all"
                            placeholder="Partagez votre expérience avec ce propriétaire..."
                        >{{ old('comment') }}</textarea>
                        <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                    </div>

                    {{-- Actions --}}
                    <div class="pt-4 border-t border-gray-100">
                        <button type="submit" class="w-full py-4 rounded-xl font-bold text-white bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 hover:from-indigo-700 hover:via-purple-700 hover:to-pink-600 transition-all shadow-lg hover:shadow-xl active:scale-[0.98] transform flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            Envoyer mon avis
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
