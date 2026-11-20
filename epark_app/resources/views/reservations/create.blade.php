<x-app-layout>
    @php
        $placesPayload = $places->map(function ($place) use ($placeSegments, $segments) {
            return [
                'id' => $place->id,
                'nom' => $place->nom,
                'adresse' => $place->site?->adresse ?? '',
                'site_id' => $place->site_id,
                'site_nom' => $place->site?->nom ?? 'Parking',
                'hourly_price' => ($place->hourly_price_cents ?? 0) / 100,
                'availableSegments' => $placeSegments[$place->id] ?? [],
                'quotes' => collect($segments)->map(fn ($period, $key) => \App\Support\BookingPeriod::amount($place->hourly_price_cents ?? 0, $key))->all(),
            ];
        })->values();
    @endphp

    <div class="booking-page" x-data="{
        places: @js($placesPayload),
        segments: @js($segments),
        selectedSite: '{{ $selectedSiteId ?? '' }}',
        selectedSegment: '{{ old('segment', '') }}',
        selectedPlace: '{{ old('place_id', $selectedPlaceId ?? '') }}',
        init() {
            this.pickDefaults(true);
        },
        get sitePlaces() {
            return this.places.filter(p => !this.selectedSite || String(p.site_id) === String(this.selectedSite));
        },
        segmentCount(key) {
            return this.sitePlaces.filter(p => p.availableSegments.includes(key)).length;
        },
        get availablePlaces() {
            return this.sitePlaces.filter(p => p.availableSegments.includes(this.selectedSegment));
        },
        get currentPlace() {
            return this.availablePlaces.find(p => String(p.id) === String(this.selectedPlace));
        },
        money(cents) {
            return new Intl.NumberFormat('fr-CH', { style: 'currency', currency: 'CHF' }).format((cents || 0) / 100);
        },
        pickDefaults(allowSegmentSwitch = false) {
            if (!this.segments[this.selectedSegment] || (allowSegmentSwitch && !this.segmentCount(this.selectedSegment))) {
                this.selectedSegment = Object.keys(this.segments).find(key => this.segmentCount(key)) || 'matin_travail';
            }
            if (!this.currentPlace) {
                this.selectedPlace = String(this.availablePlaces[0]?.id || '');
            }
        }
    }">
        <div class="booking-heading">
            <div>
                <h1>Réserver une place</h1>
                <p>Formulaire classique aligné sur les créneaux complets disponibles.</p>
            </div>
            <a href="{{ route('express.index', ['date' => $selectedDate]) }}" class="booking-secondary">Version rapide</a>
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

        {{-- Changement de date côté serveur (fonctionne aussi sans JavaScript) --}}
        <form method="GET" action="{{ route('reservations.create') }}" class="ep-card p-5 mb-6 flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[200px]">
                <label for="date-filter" class="block text-sm font-semibold mb-1.5">Date souhaitée</label>
                <input id="date-filter" type="date" name="date" value="{{ $selectedDate }}" min="{{ $minDate }}" max="{{ $maxDate }}" class="ep-input" onchange="this.form.submit()">
            </div>
            @if($selectedSiteId)
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
            @endif
            <button type="submit" class="booking-secondary">Actualiser les disponibilités</button>
        </form>

        <form method="POST" action="{{ route('reservations.store') }}" class="booking-grid">
            @csrf
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <input type="hidden" name="battement" value="5">

            <div class="booking-choices">
                <section class="booking-section">
                    <h2>1. Créneau</h2>
                    <input type="hidden" name="segment" :value="selectedSegment">
                    <div class="segment-options" role="group" aria-label="Créneau horaire">
                        @foreach($segments as $key => $segment)
                            <button
                                type="button"
                                @click="selectedSegment = '{{ $key }}'; pickDefaults(false)"
                                :aria-pressed="selectedSegment === '{{ $key }}'"
                                :class="{ 'is-selected': selectedSegment === '{{ $key }}' }">
                                <strong>{{ $segment['label'] }}</strong>
                                <span>{{ $segment['hours'] }}</span>
                                <small x-text="segmentCount('{{ $key }}') + (segmentCount('{{ $key }}') === 1 ? ' place' : ' places')"></small>
                            </button>
                        @endforeach
                    </div>
                    <noscript>
                        <div class="mt-4">
                            <label for="noscript-segment" class="block text-sm font-semibold mb-1.5">Choisir un créneau</label>
                            <select id="noscript-segment" name="segment" class="ep-input" required>
                                @foreach($segments as $key => $segment)
                                    <option value="{{ $key }}" @selected(old('segment') === $key)>{{ $segment['label'] }} ({{ $segment['hours'] }})</option>
                                @endforeach
                            </select>
                        </div>
                    </noscript>
                </section>

                <section class="booking-section">
                    <div class="section-heading">
                        <h2>2. Place</h2>
                        <span class="text-sm text-slate-600" x-text="availablePlaces.length + ' place(s) disponible(s)'" aria-live="polite"></span>
                    </div>

                    @if($sites->count() > 1 || $selectedSiteId)
                        <div class="mb-4">
                            <label for="site-filter" class="sr-only">Parking</label>
                            <select id="site-filter" x-model="selectedSite" @change="pickDefaults(true)" class="booking-select">
                                <option value="">Tous les parkings</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}">{{ $site->nom }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <input type="hidden" name="place_id" :value="selectedPlace">

                    <div class="place-options">
                        @foreach($places as $place)
                            <button
                                type="button"
                                x-show="(!selectedSite || String({{ $place->site_id }}) === String(selectedSite)) && @js($placeSegments[$place->id] ?? []).includes(selectedSegment)"
                                @click="selectedPlace = '{{ $place->id }}'"
                                :aria-pressed="String(selectedPlace) === '{{ $place->id }}'"
                                :class="{ 'is-selected': String(selectedPlace) === '{{ $place->id }}' }">
                                <span class="parking-letter" aria-hidden="true">P</span>
                                <span class="place-description">
                                    <strong>{{ $place->nom }} · {{ $place->site?->nom ?? 'Parking' }}</strong>
                                    <span>{{ $place->site?->adresse ?? '' }}</span>
                                </span>
                                <span class="place-price">
                                    <strong x-text="money(@js(collect($segments)->map(fn ($p, $k) => \App\Support\BookingPeriod::amount($place->hourly_price_cents ?? 0, $k))->all())[selectedSegment])">{{ format_chf(($place->hourly_price_cents ?? 0) / 100) }}/h</strong>
                                    <small>le créneau</small>
                                </span>
                            </button>
                        @endforeach
                    </div>

                    <noscript>
                        <div class="mt-4">
                            <label for="noscript-place" class="block text-sm font-semibold mb-1.5">Choisir une place</label>
                            <select id="noscript-place" name="place_id" class="ep-input" required>
                                @foreach($places as $place)
                                    @if(! empty($placeSegments[$place->id]))
                                        <option value="{{ $place->id }}" @selected((string) old('place_id', $selectedPlaceId) === (string) $place->id)>
                                            {{ $place->nom }} · {{ $place->site?->nom }} ({{ format_chf(($place->hourly_price_cents ?? 0) / 100) }}/h)
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </noscript>

                    <div x-show="!availablePlaces.length" x-cloak class="booking-empty" role="status">
                        <h3>Aucune place disponible pour ce créneau</h3>
                        <p>Essayez un autre horaire ou une autre date, ou proposez votre propre place de parc.</p>
                        <div class="mt-4">
                            <a href="{{ route('places.create') }}" class="booking-primary">Proposer une place</a>
                        </div>
                        @if(!empty($savedGroupEntries))
                            <p class="mt-3 text-xs text-slate-600">
                                Groupes actifs : {{ collect($savedGroupEntries)->pluck('name')->filter()->implode(', ') }}
                            </p>
                        @else
                            <p class="mt-3 text-sm">Accès privé ? <a href="{{ route('profile.edit') }}#secret-groups" class="text-link">Ajouter mon groupe</a></p>
                        @endif
                    </div>
                </section>
            </div>

            <aside class="booking-summary">
                <h2>Récapitulatif</h2>
                <p class="summary-date">{{ \Illuminate\Support\Carbon::parse($selectedDate)->locale('fr')->isoFormat('dddd D MMMM') }}</p>
                <p x-text="segments[selectedSegment]?.hours || 'Choisissez un créneau'"></p>
                <div class="summary-place">
                    <strong x-text="currentPlace ? currentPlace.nom + ' · ' + currentPlace.site_nom : 'Aucune place sélectionnée'"></strong>
                    <p x-text="currentPlace?.adresse || ''"></p>
                </div>
                <div class="mb-4">
                    <label for="classic-vehicle-plate" class="block text-xs font-semibold text-slate-600 mb-1">Plaque d’immatriculation <span class="font-normal text-slate-500">(facultatif)</span></label>
                    <input id="classic-vehicle-plate" type="text" name="vehicle_plate" maxlength="20" value="{{ old('vehicle_plate', auth()->user()->quick_vehicle_plate) }}" placeholder="Ex. : VD 123456" class="ep-input uppercase">
                </div>
                <div class="summary-total">
                    <span>Total du créneau</span>
                    <strong x-text="currentPlace ? money(currentPlace.quotes[selectedSegment]) : '—'">—</strong>
                </div>
                <p class="summary-note">Demande soumise au propriétaire. Règlement manuel, aucun débit en ligne.</p>
                <button type="submit" class="booking-primary w-full" :disabled="!currentPlace">Demander cette place</button>
            </aside>
        </form>
    </div>
</x-app-layout>
