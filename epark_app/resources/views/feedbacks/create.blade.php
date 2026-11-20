<x-app-layout>
    <div class="booking-page max-w-2xl">
        <a href="{{ route('reservations.show', $reservation) }}" class="ep-back-link">
            <span aria-hidden="true">←</span> Retour à la réservation #{{ $reservation->id }}
        </a>

        <div class="booking-heading">
            <div>
                <h1>Donner votre avis</h1>
                <p>Partagez votre retour sur cette réservation terminée.</p>
            </div>
        </div>

        <section class="ep-card overflow-hidden">
            <div class="p-6 bg-[#f3f6fa] border-b border-[#dce3ed]">
                <dl class="pass-details !mt-0">
                    <div>
                        <dt>Site</dt>
                        <dd>{{ $reservation->place?->site?->nom ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt>Place</dt>
                        <dd>{{ $reservation->place?->nom ?? 'Place archivée' }}</dd>
                    </div>
                    <div>
                        <dt>Date</dt>
                        <dd>{{ $reservation->date_debut->format('d.m.Y') }}, {{ $reservation->date_debut->format('H:i') }}–{{ $reservation->date_fin->format('H:i') }}</dd>
                    </div>
                    <div>
                        <dt>Propriétaire</dt>
                        <dd>{{ $reservation->place?->user?->name ?? 'N/A' }}</dd>
                    </div>
                </dl>
            </div>

            <form action="{{ route('reservations.feedback.store', $reservation) }}" method="POST" class="p-6 space-y-6">
                @csrf

                <div x-data="{ rating: {{ (int) old('rating', 0) }}, hoverRating: 0 }">
                    <span class="block text-sm font-semibold mb-1">Comment évaluez-vous cette expérience ? <span class="text-rose-700">*</span></span>
                    <div class="flex flex-wrap items-center gap-1 mt-2" role="radiogroup" aria-label="Note sur 5">
                        <template x-for="star in 5" :key="star">
                            <button
                                type="button"
                                @click="rating = star"
                                @mouseenter="hoverRating = star"
                                @mouseleave="hoverRating = 0"
                                :aria-label="star + ' étoile' + (star > 1 ? 's' : '') + ' sur 5'"
                                :aria-pressed="rating === star"
                                class="text-3xl p-1 rounded-lg transition-colors"
                                :class="(hoverRating || rating) >= star ? 'text-amber-500' : 'text-slate-300'"
                            >★</button>
                        </template>
                        <span class="ml-3 text-sm text-slate-600" x-show="rating > 0" x-cloak>
                            <strong x-text="rating + '/5'"></strong>
                            <span x-show="rating === 1"> — Très mauvais</span>
                            <span x-show="rating === 2"> — Mauvais</span>
                            <span x-show="rating === 3"> — Correct</span>
                            <span x-show="rating === 4"> — Bien</span>
                            <span x-show="rating === 5"> — Excellent</span>
                        </span>
                    </div>
                    <input type="hidden" name="rating" x-model="rating">
                    <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                </div>

                <div>
                    <label for="comment" class="block text-sm font-semibold mb-2">Commentaire (optionnel)</label>
                    <textarea
                        id="comment"
                        name="comment"
                        rows="4"
                        maxlength="1000"
                        class="ep-input"
                        placeholder="Ex. : Accès facile, place bien indiquée…"
                    >{{ old('comment') }}</textarea>
                    <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-[#dce3ed]">
                    <a href="{{ route('reservations.show', $reservation) }}" class="booking-secondary">Annuler</a>
                    <button type="submit" class="booking-primary">Envoyer mon avis</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
