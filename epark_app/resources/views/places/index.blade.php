<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight flex items-center gap-3">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                Explorer les places
                </h2>
                <p class="text-sm text-gray-500 mt-1">Trouvez une place disponible et reservez en quelques clics.</p>
            </div>
            <a href="{{ route('reservations.create') }}" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition-all flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                Recherche avancée
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-50 border border-green-200 text-green-700 rounded-2xl flex items-center gap-3 shadow-sm animate-slide-in">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Barre de recherche et filtres -->
            <div class="mb-8" x-data="{ showFilters: false }">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <input type="text" placeholder="Rechercher par nom ou adresse..." 
                                   class="w-full rounded-xl border-gray-200 pl-12 pr-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all" 
                                   x-on:input="filterPlaces($event.target.value)">
                            <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <button @click="showFilters = !showFilters" 
                                class="px-6 py-3 rounded-xl border-2 font-bold transition-all"
                                :class="showFilters ? 'bg-indigo-50 text-indigo-600 border-indigo-200' : 'bg-white text-gray-600 border-gray-200 hover:border-gray-300'">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            Filtres
                        </button>
                    </div>
                    
                    <div x-show="showFilters" x-transition class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 md:grid-cols-3 gap-4">
                        <select class="rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500">
                            <option value="">Prix : Tous</option>
                            <option value="low">Prix croissant</option>
                            <option value="high">Prix décroissant</option>
                        </select>
                        <select class="rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500">
                            <option value="">Disponibilité : Toutes</option>
                            <option value="now">Disponible maintenant</option>
                            <option value="today">Disponible aujourd'hui</option>
                        </select>
                        <select class="rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500">
                            <option value="">Quartier : Tous</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($places as $place)
                    <div class="bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-2 transition-all duration-300 group">
                        <div class="h-40 bg-gradient-to-br from-indigo-400 via-purple-400 to-indigo-500 relative items-center justify-center flex overflow-hidden">
                            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0di00aC0ydjRoLTR2Mmg0djRoMnYtNGg0di0yaC00em0wLTMwVjBoLTJ2NGgtNHYyaDR2NGgyVjZoNFY0aC00ek02IDM0di00SDR2NGg2djJINFY0aDJ2NGg0VjZINlY0aDR2Mmg0djJoLTR2Mmg0djJoLTR2Mmg0djJoLTQiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-30"></div>
                            <svg class="w-16 h-16 text-white/60 group-hover:scale-125 group-hover:rotate-12 transition-all duration-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            <div class="absolute top-3 right-3 py-1.5 px-3 bg-white/95 backdrop-blur rounded-xl text-xs font-black text-indigo-600 shadow-lg">
                                {{ format_chf($place->hourly_price_cents / 100) }}/h
                            </div>
                            <div class="absolute top-3 left-3 py-1 px-2 bg-green-500 text-white rounded-lg text-[10px] font-bold">
                                DISPO
                            </div>
                        </div>
                        
                        <div class="p-5">
                            <div class="mb-4">
                                <h3 class="text-lg font-black text-gray-900 group-hover:text-indigo-600 transition-colors mb-2">{{ $place->nom }}</h3>
                                <p class="text-gray-500 text-xs flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path></svg>
                                    {{ Str::limit(optional($place->site)->adresse ?? 'Adresse non spécifiée', 30) }}
                                </p>
                                @if($place->caracteristiques)
                                    <p class="text-gray-400 text-xs mt-2 line-clamp-2">{{ Str::limit($place->caracteristiques, 60) }}</p>
                                @endif
                            </div>
                            
                            <!-- Statistiques mini -->
                            <div class="flex items-center gap-3 mb-4 text-xs">
                                <span class="text-gray-500 font-medium">{{ optional($place->site)->nom ?? 'Site' }}</span>
                            </div>
                            
                            <div class="border-t border-gray-50 pt-4 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-black text-indigo-600">
                                        {{ strtoupper(substr(optional($place->user)->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <span class="text-[10px] font-bold text-gray-400">{{ Str::limit(optional($place->user)->name ?? 'Anonyme', 12) }}</span>
                                </div>
                                <a href="{{ route('reservations.create', ['place_id' => $place->id]) }}" class="bg-indigo-600 text-white px-5 py-2 rounded-xl text-xs font-black hover:bg-indigo-700 hover:shadow-lg hover:shadow-indigo-200 transition-all transform hover:scale-105">
                                    Réserver
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-gray-100">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Aucune place disponible</h3>
                        <p class="text-gray-500 mt-2">Revenez plus tard ou changez vos filtres de recherche.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
