<x-app-layout>
    <div class="booking-page max-w-2xl">
        <a href="{{ route('places.mes') }}" class="ep-back-link">
            <span aria-hidden="true">←</span> Retour à mes places
        </a>

        <div class="booking-heading">
            <div>
                <h1>Modifier la place</h1>
                <p>{{ $place->nom }} · {{ $place->site?->nom ?? 'Site' }}</p>
            </div>
            <a href="{{ route('places.blocked-slots.edit', $place->id) }}" class="booking-secondary">Disponibilités</a>
        </div>

        @if($errors->any())
            <div class="booking-error" role="alert">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="ep-card p-6 sm:p-8">
            <form method="POST" action="{{ route('places.update', $place->id) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="nom" class="block text-sm font-semibold mb-1.5">Nom de la place <span class="text-rose-700">*</span></label>
                    <input type="text" id="nom" name="nom" value="{{ old('nom', $place->nom) }}" class="ep-input" required>
                </div>

                <div class="p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed]">
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $place->is_active ? '1' : '0') == '1')
                            class="mt-1 rounded border-slate-300 text-[#4338ca] focus:ring-[#4338ca]">
                        <span>
                            <strong class="block text-sm">Place active et visible à la réservation</strong>
                            <span class="block text-xs text-slate-600 mt-0.5">Décochez pour mettre la place temporairement en pause sans supprimer son historique ni ses règles.</span>
                        </span>
                    </label>
                </div>

                <div>
                    <label for="cancel_deadline_hours" class="block text-sm font-semibold mb-1.5">Délai d’annulation <span class="text-rose-700">*</span></label>
                    <select id="cancel_deadline_hours" name="cancel_deadline_hours" class="ep-input" required>
                        <option value="12" @selected(old('cancel_deadline_hours', (string) ($place->cancel_deadline_hours ?? 24)) === '12')>12 heures avant</option>
                        <option value="24" @selected(old('cancel_deadline_hours', (string) ($place->cancel_deadline_hours ?? 24)) === '24')>24 heures avant</option>
                    </select>
                    <p class="mt-1.5 text-xs text-slate-600">Les locataires pourront annuler ou modifier leur demande jusqu’à ce délai.</p>
                </div>

                <div>
                    <label for="caracteristiques" class="block text-sm font-semibold mb-1.5">Description / caractéristiques</label>
                    <textarea id="caracteristiques" name="caracteristiques" rows="4" class="ep-input">{{ old('caracteristiques', $place->caracteristiques) }}</textarea>
                </div>

                <div>
                    <label for="hourly_price" class="block text-sm font-semibold mb-1.5">Tarif horaire (CHF) <span class="text-rose-700">*</span></label>
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <input
                                type="number"
                                id="hourly_price"
                                name="hourly_price"
                                value="{{ old('hourly_price', number_format(($place->hourly_price_cents ?? 0) / 100, 2, '.', '')) }}"
                                min="0"
                                step="0.05"
                                class="ep-input pr-14"
                                required>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500">CHF/h</span>
                        </div>
                        <button type="button" onclick="document.getElementById('hourly_price').value='0.00'" class="booking-secondary">Gratuit</button>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-[#dce3ed]">
                    <a href="{{ route('places.mes') }}" class="booking-secondary">Annuler</a>
                    <button type="submit" class="booking-primary">Enregistrer</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
