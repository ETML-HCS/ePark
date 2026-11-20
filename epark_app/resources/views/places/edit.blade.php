<x-app-layout>
    @php
        $openAdvanced = $errors->hasAny(['caracteristiques', 'cancel_deadline_hours']);
    @endphp
    <div class="booking-page max-w-3xl">
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

                <div class="p-3.5 rounded-xl bg-[#f3f6fa] border border-[#dce3ed]">
                    <input type="hidden" name="is_active" value="0">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', $place->is_active ? '1' : '0') == '1')
                            class="rounded border-slate-300 text-[#4338ca] focus:ring-[#4338ca]">
                        <span class="text-sm font-semibold">Place active et visible à la réservation</span>
                    </label>
                </div>

                @include('places.partials.rental-rules-editor', ['place' => $place])

                <details class="rounded-xl border border-[#dce3ed] bg-[#f8fafc]" @if($openAdvanced) open @endif>
                    <summary class="cursor-pointer list-none px-4 py-3.5 flex items-center justify-between gap-2 text-sm font-semibold text-[#18263a] hover:bg-slate-100/70 rounded-xl">
                        <span>
                            Options avancées
                            <span class="font-normal text-xs text-slate-500 ml-1">· Description, délai d’annulation ({{ (int) ($place->cancel_deadline_hours ?? 24) }}h)</span>
                        </span>
                        <span aria-hidden="true" class="text-slate-400 text-xs">▾</span>
                    </summary>

                    <div class="px-4 pb-5 pt-3 space-y-4 border-t border-[#dce3ed]">
                        <div>
                            <label for="cancel_deadline_hours" class="block text-xs font-semibold text-slate-700 mb-1.5">Délai d’annulation</label>
                            <select id="cancel_deadline_hours" name="cancel_deadline_hours" class="ep-input" required>
                                <option value="12" @selected(old('cancel_deadline_hours', (string) ($place->cancel_deadline_hours ?? 24)) === '12')>12 heures avant</option>
                                <option value="24" @selected(old('cancel_deadline_hours', (string) ($place->cancel_deadline_hours ?? 24)) === '24')>24 heures avant</option>
                            </select>
                        </div>

                        <div>
                            <label for="caracteristiques" class="block text-xs font-semibold text-slate-700 mb-1.5">Description / caractéristiques</label>
                            <textarea id="caracteristiques" name="caracteristiques" rows="3" class="ep-input">{{ old('caracteristiques', $place->caracteristiques) }}</textarea>
                        </div>
                    </div>
                </details>

                <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-[#dce3ed]">
                    <a href="{{ route('places.mes') }}" class="booking-secondary">Annuler</a>
                    <button type="submit" class="booking-primary">Enregistrer</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
