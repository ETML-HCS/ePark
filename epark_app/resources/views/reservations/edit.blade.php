<x-app-layout>
    <div class="booking-page max-w-2xl">
        <a href="{{ route('reservations.show', $reservation) }}" class="ep-back-link">
            <span aria-hidden="true">←</span> Retour à la réservation #{{ $reservation->id }}
        </a>

        <div class="booking-heading">
            <div>
                <h1>Modifier la réservation</h1>
                <p>{{ $place?->nom ?? 'Place' }} · {{ $place?->site?->nom ?? '' }}</p>
            </div>
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

        @if($place?->hasRentalRules())
            <section class="ep-card p-5 mb-6">
                <h2 class="text-sm font-bold mb-3">Plages &amp; tarifs de cette place</h2>
                @include('places.partials.weekly-visual-calendar', ['place' => $place])
            </section>
        @endif

        <section class="ep-card p-6">
            <form method="POST" action="{{ route('reservations.update', $reservation) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label for="date" class="block text-sm font-semibold mb-2">Nouvelle date</label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        min="{{ $minDate }}"
                        max="{{ $maxDate }}"
                        value="{{ old('date', $selectedDate) }}"
                        class="ep-input"
                        required>
                </div>

                <div>
                    <label for="segment" class="block text-sm font-semibold mb-2">Créneau</label>
                    <select id="segment" name="segment" class="ep-input" required>
                        @foreach(($segments ?? \App\Support\BookingPeriod::SEGMENTS) as $key => $segment)
                            <option value="{{ $key }}" @selected(old('segment', $selectedSegment) === $key)>{{ $segment['label'] }} ({{ $segment['hours'] }})</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-slate-600">
                        Le montant sera recalculé automatiquement selon la durée du créneau. Délai d’annulation / modification : {{ (int) ($place->cancel_deadline_hours ?? 24) }} h avant le début.
                    </p>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-4 border-t border-[#dce3ed]">
                    <a href="{{ route('reservations.show', $reservation) }}" class="booking-secondary">Annuler</a>
                    <button type="submit" class="booking-primary">Enregistrer les modifications</button>
                </div>
            </form>
        </section>
    </div>
</x-app-layout>
