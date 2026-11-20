<x-app-layout>
    <div class="booking-page">
        <div class="booking-heading"><div><h1>{{ auth()->check() ? 'Les places de parking' : 'Une place pour votre prochain trajet' }}</h1><p>Choisissez votre créneau, envoyez votre demande, retrouvez la confirmation ici.</p></div></div>
        <div class="flex flex-wrap gap-3 mb-8"><a href="{{ auth()->check() ? route('express.index') : route('login') }}" class="booking-primary">{{ auth()->check() ? 'Réserver une place' : 'Me connecter pour réserver' }}</a>@guest<a href="{{ route('register') }}" class="booking-secondary">Créer mon compte</a>@else<a href="{{ route('places.create') }}" class="booking-secondary">Proposer une place</a>@endguest</div>
        @auth
            <details class="mb-6 bg-white border border-slate-200 rounded-xl p-4"><summary class="cursor-pointer font-semibold text-sm">Accès à un parking privé</summary><p class="text-sm text-slate-600 mt-3 mb-3">Vos groupes enregistrés sont appliqués automatiquement. Vous pouvez aussi vérifier un code ici.</p><form method="GET" action="{{ route('home') }}" class="flex flex-wrap gap-3"><div class="flex-1"><label for="group_code" class="sr-only">Code du groupe</label><input id="group_code" name="group_code" type="password" placeholder="Code du groupe" autocomplete="off" class="booking-select"></div><button type="submit" class="booking-secondary">Afficher les places</button></form><a href="{{ route('profile.edit') }}#secret-groups" class="text-link text-sm inline-block mt-3">Enregistrer mon groupe dans mon profil</a></details>
        @endauth

        @if(!empty($mapSites))
            <section class="ep-card p-5 mb-8" x-data="parkingMap(@js($mapSites))">
                <div class="section-heading !mb-3">
                    <div>
                        <h2 class="text-lg font-bold">Carte des parkings</h2>
                        <p class="text-xs text-slate-600 mt-0.5">Cliquez sur un marqueur pour consulter les places et accéder aux créneaux.</p>
                    </div>
                    <span class="status-pill is-neutral">{{ count($mapSites) }} {{ count($mapSites) > 1 ? 'sites' : 'site' }}</span>
                </div>
                <div x-ref="mapContainer" class="ep-map" aria-label="Carte interactive des parkings disponibles"></div>
            </section>
        @endif

        <section x-data="{ search: '' }">
            <div class="section-heading"><h2 class="text-xl font-bold">{{ auth()->check() ? 'Parkings accessibles' : 'Places publiques' }}</h2><span class="text-sm text-slate-600">{{ $places->count() }} place(s)</span></div>
            @if($places->isNotEmpty())<div class="mb-5"><label for="parking-search" class="sr-only">Rechercher un parking</label><input type="search" id="parking-search" x-model="search" placeholder="Nom du parking, adresse ou place…" class="booking-select max-w-lg"></div>@endif
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse($places as $place)
                    <article class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col" x-show="!search || @js(mb_strtolower($place->nom.' '.$place->site?->nom.' '.$place->site?->adresse)).includes(search.toLocaleLowerCase('fr'))">
                        <div class="flex items-center gap-3 mb-4"><span class="parking-letter" aria-hidden="true">P</span><h3 class="font-bold text-lg">{{ $place->nom }}</h3></div>
                        <p class="font-semibold text-sm">{{ $place->site?->nom }}</p><p class="text-sm text-slate-600 mt-1">{{ $place->site?->adresse }}</p>
                        @if(($place->feedbacks_count ?? 0) > 0)
                            <p class="text-xs font-semibold text-amber-700 mt-2">
                                ★ {{ number_format((float) $place->feedbacks_avg_rating, 1) }}/5
                                <span class="font-normal text-slate-500">({{ $place->feedbacks_count }} avis)</span>
                            </p>
                        @endif
                        @if($place->is_group_reserved)<p class="text-xs text-indigo-800 mt-3">Groupe privé : {{ $place->group_name }}</p>@endif
                        <div class="flex items-end justify-between gap-2 mt-auto pt-6"><p class="text-sm"><strong>{{ format_chf(($place->hourly_price_cents ?? 0) / 100) }}</strong> / heure</p><a href="{{ route('express.index', ['place_id' => $place->id, 'site_id' => $place->site_id]) }}" class="text-link text-sm">Voir les créneaux</a></div>
                    </article>
                @empty
                    <div class="booking-empty col-span-full">
                        <h3>Aucune place publique pour le moment</h3>
                        <p>Vous disposez d’une place de parc libre ? Proposez-la dès maintenant, ou ajoutez le code de votre groupe privé.</p>
                        <div class="flex flex-wrap justify-center gap-3 mt-4">
                            @auth
                                <a href="{{ route('places.create') }}" class="booking-primary">Proposer une place</a>
                                <a href="{{ route('profile.edit') }}#secret-groups" class="booking-secondary">Ajouter mon groupe</a>
                            @else
                                <a href="{{ route('register') }}" class="booking-primary">Créer un compte pour proposer une place</a>
                                <a href="{{ route('login') }}" class="booking-secondary">Me connecter</a>
                            @endauth
                        </div>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
