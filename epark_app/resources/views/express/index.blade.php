<x-app-layout>
    <div class="booking-page" x-data="expressBooking(@js($bookingConfig))">
        <div class="booking-heading">
            <div><h1>Réserver une place</h1><p>Bonjour {{ explode(' ', $user->name)[0] }}. Vos préférences sont déjà sélectionnées.</p></div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('places.create') }}" class="booking-secondary">Proposer une place</a>
                <a href="{{ route('reservations.index') }}" class="text-link">Mes réservations</a>
            </div>
        </div>

        @if(! ($hasAnyActivePlaces ?? true))
            <section class="ep-card p-6 mb-6 flex flex-wrap items-center justify-between gap-4 border-indigo-200 bg-[#eef2ff]">
                <div>
                    <h2 class="text-base font-bold text-[#312e81]">Aucune place n’est encore enregistrée sur ePark</h2>
                    <p class="text-sm text-slate-700 mt-1">Vous disposez d’une place de parc ? Proposez la première place pour ouvrir les réservations.</p>
                </div>
                <a href="{{ route('places.create') }}" class="booking-primary shrink-0">Proposer une place</a>
            </section>
        @endif

        <noscript><p class="booking-error">Activez JavaScript pour ce parcours ou utilisez le <a href="{{ route('reservations.create') }}" class="underline">formulaire classique</a>.</p></noscript>

        <section x-show="success" x-cloak class="booking-success" role="status" tabindex="-1" x-ref="success">
            <span class="status-pill">En attente du propriétaire</span>
            <h2>Votre demande est envoyée</h2>
            <p x-text="success?.message"></p>
            <dl class="pass-details">
                <div><dt>Place</dt><dd x-text="success?.place_nom + ' · ' + success?.site_nom"></dd></div>
                <div><dt>Quand</dt><dd x-text="success?.date_formatted + ', ' + success?.segment_label"></dd></div>
                <div><dt>Total</dt><dd x-text="success?.amount_chf"></dd></div>
                <div><dt>Paiement</dt><dd x-text="success?.paid ? 'Gratuit, aucun paiement nécessaire' : 'À régler au propriétaire, aucun débit en ligne'"></dd></div>
            </dl>
            <div class="flex flex-wrap gap-3 mt-6">
                <a :href="success?.url" class="booking-primary">Voir ma demande</a>
                <button type="button" @click="newBooking()" class="booking-secondary">Réserver un autre créneau</button>
            </div>
        </section>

        <form @submit.prevent="book" x-show="!success" class="booking-grid" :aria-busy="loading || booking">
            <div class="booking-choices">
                <section class="booking-section">
                    <h2>Quand ?</h2>
                    <div class="date-options">
                        <button type="button" @click="setDate(config.today)" :aria-pressed="date === config.today" :disabled="booking" :class="{ 'is-selected': date === config.today }">Aujourd’hui<span>{{ now()->locale('fr')->isoFormat('D MMM') }}</span></button>
                        <button type="button" @click="setDate(config.tomorrow)" :aria-pressed="date === config.tomorrow" :disabled="booking" :class="{ 'is-selected': date === config.tomorrow }">Demain<span>{{ now()->addDay()->locale('fr')->isoFormat('D MMM') }}</span></button>
                        <div><label for="booking-date">Autre date</label><input id="booking-date" type="date" :value="date" @change="setDate($event.target.value)" :min="config.today" :max="config.maxDate" :disabled="booking" required></div>
                    </div>
                    <div class="segment-options" aria-label="Créneau">
                        <template x-for="(segment, key) in config.segments" :key="key">
                            <button type="button" @click="setSegment(key)" :disabled="loading || booking" :aria-pressed="selectedSegment === key" :class="{ 'is-selected': selectedSegment === key }">
                                <strong x-text="segment.label"></strong><span x-text="segment.hours"></span>
                                <small x-text="segmentCount(key) + (segmentCount(key) === 1 ? ' place' : ' places')"></small>
                            </button>
                        </template>
                    </div>
                </section>

                <section class="booking-section" :class="{ 'opacity-60': loading }">
                    <div class="section-heading"><h2>Où ?</h2><span class="text-sm text-slate-600" x-text="loading ? 'Actualisation…' : availablePlaces.length + ' place(s) disponible(s)'" aria-live="polite"></span></div>
                    <div x-show="config.sites.length > 1 || selectedSiteId" class="mb-4">
                        <label for="booking-site" class="sr-only">Parking</label>
                        <select id="booking-site" x-ref="siteSelect" x-model="selectedSiteId" @change="chooseDefaults(true)" :disabled="loading || booking" class="booking-select">
                            <option value="">Tous les parkings</option>
                            <template x-for="site in config.sites" :key="site.id"><option :value="String(site.id)" x-text="site.nom"></option></template>
                        </select>
                    </div>
                    <div class="place-options">
                        <template x-for="place in availablePlaces" :key="place.id">
                            <button type="button" @click="selectedPlaceId = String(place.id)" :disabled="loading || booking" :aria-pressed="selectedPlaceId === String(place.id)" :class="{ 'is-selected': selectedPlaceId === String(place.id) }">
                                <span class="parking-letter" aria-hidden="true">P</span>
                                <span class="place-description">
                                    <strong x-text="place.nom + ' · ' + place.site_nom"></strong>
                                    <span x-text="place.adresse"></span>
                                    <small x-show="place.rating_count > 0" class="block text-xs font-semibold text-amber-700 mt-1" x-text="'★ ' + place.rating_avg + ' (' + place.rating_count + ' avis)'"></small>
                                </span>
                                <span class="place-price"><strong x-text="money(place.quotes[selectedSegment])"></strong><small>le créneau</small></span>
                            </button>
                        </template>
                    </div>
                    <div x-show="!loading && !availablePlaces.length" x-cloak class="booking-empty" role="status">
                        <h3>Aucune place pour ce créneau</h3>
                        <p>Choisissez une autre heure ou une autre date, ou proposez votre propre place de parc.</p>
                        <div class="flex flex-wrap justify-center gap-3 mt-4">
                            <a href="{{ route('places.create') }}" class="booking-primary">Proposer une place</a>
                            <button x-show="selectedSiteId" type="button" @click="selectedSiteId = ''; chooseDefaults(true)" class="booking-secondary">Chercher dans tous les parkings</button>
                        </div>
                        <p class="mt-3 text-sm">Vous avez un accès privé ? <a href="{{ route('profile.edit') }}#secret-groups" class="text-link">Ajouter mon groupe</a></p>
                    </div>
                </section>
            </div>

            <aside class="booking-summary">
                <h2>Votre réservation</h2>
                <p class="summary-date" x-text="formattedDate"></p>
                <p x-text="config.segments[selectedSegment]?.hours || 'Choisissez un créneau'"></p>
                <div class="summary-place"><strong x-text="currentPlace ? currentPlace.nom + ' · ' + currentPlace.site_nom : 'Aucune place sélectionnée'"></strong><p x-text="currentPlace?.adresse"></p></div>
                <div class="mb-4">
                    <label for="booking-plate" class="block text-xs font-semibold text-slate-600 mb-1">Plaque d’immatriculation <span class="font-normal text-slate-500">(facultatif)</span></label>
                    <input id="booking-plate" type="text" x-model="vehiclePlate" maxlength="20" placeholder="Ex. : VD 123456" class="ep-input uppercase" :disabled="booking">
                </div>
                <div class="summary-total"><span>Total du créneau</span><strong x-text="currentPlace ? money(currentPlace.quotes[selectedSegment]) : '—'"></strong></div>
                <p class="summary-note">Demande soumise au propriétaire. Aucun débit en ligne.</p>
                <div x-show="error" x-cloak class="booking-error" role="alert"><p x-text="error"></p><a x-show="sessionExpired" href="{{ route('login') }}" class="text-link">Me reconnecter</a><button x-show="loadFailed" type="button" @click="setDate(date, true)" class="text-link">Réessayer</button></div>
                <div class="booking-submit-bar" x-show="!success">
                    <div class="mobile-total"><strong x-text="currentPlace ? money(currentPlace.quotes[selectedSegment]) : '—'"></strong><span x-text="currentPlace?.nom || 'Choisissez une place'"></span></div>
                    <button type="submit" class="booking-primary" :disabled="!currentPlace || loading || booking || loadFailed"><span x-text="booking ? 'Envoi…' : loading ? 'Actualisation…' : 'Demander cette place'"></span></button>
                </div>
            </aside>
        </form>

        @if($activeReservations->isNotEmpty())
            <section class="upcoming-bookings"><div class="section-heading"><h2>À venir</h2><a href="{{ route('reservations.index') }}" class="text-link">Tout voir</a></div>
                @foreach($activeReservations as $reservation)
                    <a href="{{ route('reservations.show', $reservation) }}" class="upcoming-row"><div><strong>{{ $reservation->place?->nom ?? 'Place archivée' }} · {{ $reservation->place?->site?->nom }}</strong><p>{{ $reservation->date_debut->format('d.m.Y') }}, {{ $reservation->date_debut->format('H:i') }}–{{ $reservation->date_fin->format('H:i') }}</p></div><span class="status-pill {{ $reservation->statut === 'confirmée' ? 'is-confirmed' : '' }}">{{ $reservation->statut === 'confirmée' ? 'Confirmée' : 'En attente' }}</span></a>
                @endforeach
            </section>
        @endif

        <details class="booking-settings" x-data="{ copied: false, copyError: '' }">
            <summary id="preferences">Mon accès rapide et mes préférences</summary>
            <div class="settings-content">
                <p>Gardez ce lien privé : il permet de vous connecter à votre compte sans mot de passe.</p>
                <label for="direct-link">Mon lien personnel</label><input id="direct-link" type="text" readonly value="{{ $directLink }}" @focus="$el.select()">
                <div class="flex flex-wrap gap-3"><button type="button" class="booking-secondary" @click="navigator.clipboard.writeText($refs.link.value).then(() => copied = true).catch(() => copyError = 'Sélectionnez le lien ci-dessus pour le copier.')" x-text="copied ? 'Lien copié' : 'Copier mon lien'"></button><a href="{{ route('profile.edit') }}" class="booking-secondary">Mon profil et mes groupes</a></div>
                <input type="hidden" x-ref="link" value="{{ $directLink }}"><p role="status" x-text="copyError"></p>
                <div class="flex flex-wrap gap-5"><form method="POST" action="{{ route('express.regenerate-key') }}" onsubmit="return confirm('Remplacer votre clé ? Les anciens liens ne permettront plus de vous connecter.');">@csrf<button class="text-link" type="submit">Remplacer ma clé</button></form><form method="POST" action="{{ route('express.forget') }}">@csrf<button class="text-link" type="submit">Déconnecter et oublier cet appareil</button></form></div>
            </div>
        </details>
    </div>
</x-app-layout>
