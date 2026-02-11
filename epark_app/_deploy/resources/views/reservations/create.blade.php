<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-xl text-gray-800 leading-tight">Réserver une place</h2>
            <a href="{{ route('home') }}" class="text-sm text-indigo-600 font-semibold hover:text-indigo-700">Voir les places</a>
        </div>
    </x-slot>

    @php
        $placesData = $places->map(function($place) {
            return [
                'id' => $place->id,
                'nom' => $place->nom,
                'adresse' => optional($place->site)->adresse,
                'site_id' => optional($place->site)->id,
                'site_nom' => optional($place->site)->nom,
                'hourly_price' => $place->hourly_price_cents ? $place->hourly_price_cents / 100 : 0,
            ];
        });
    @endphp

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>

    <div class="py-6">
        <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-4" x-data="{
                selectedDate: {{ json_encode($selectedDate) }},
                todayDate: {{ json_encode($minDate) }},
                selectedSite: {{ json_encode($selectedSiteId ?? '') }},
                selectedPlace: {{ json_encode($selectedPlaceId ?? '') }},
                showBattement: false,
                selectedSegment: 'matin_travail',
                paymentDone: false,
                places: {{ json_encode($placesData) }},
                placeHours: {{ json_encode($placeHours) }},
                segments: {
                    nuit: { label: 'Nuit', startMin: 0, endMin: 450, hours: '00:00-07:30' },
                    matin_travail: { label: 'Matin travail', startMin: 480, endMin: 720, hours: '08:00-12:00' },
                    aprem_travail: { label: 'Aprem travail', startMin: 720, endMin: 1050, hours: '12:00-17:30' },
                    soir: { label: 'Soir', startMin: 1080, endMin: 1440, hours: '18:00-23:59' }
                },
                init() {
                    const list = this.filteredPlaces();
                    if (!this.selectedPlace && list.length) {
                        this.selectPlace(list[0].id);
                        return;
                    }
                },
                applyDate() {
                    let url = '{{ route('reservations.create') }}' + '?date=' + this.selectedDate;
                    if (this.selectedSite) url += '&site_id=' + this.selectedSite;
                    if (this.selectedPlace) url += '&place_id=' + this.selectedPlace;
                    window.location = url;
                },
                setToday() {
                    this.selectedDate = this.todayDate;
                    this.applyDate();
                },
                setSite(siteId) {
                    this.selectedSite = siteId;
                    const list = this.filteredPlaces();
                    if (!list.length) {
                        this.selectedPlace = '';
                        return;
                    }
                    this.selectPlace(list[0].id);
                },
                setSegment(segmentKey) {
                    this.selectedSegment = segmentKey;
                    const list = this.filteredPlaces();
                    if (!list.length) {
                        this.selectedPlace = '';
                        return;
                    }
                    const hasPlace = this.selectedPlace && list.some(p => String(p.id) === String(this.selectedPlace));
                    if (!hasPlace) {
                        this.selectPlace(list[0].id);
                        return;
                    }
                },
                filteredPlaces() {
                    let list = this.places;
                    if (this.selectedSite) {
                        list = list.filter(p => String(p.site_id || '') === String(this.selectedSite));
                    }
                    return list.filter(p => this.placeHasSegment(p.id, this.selectedSegment));
                },
                hoursForPlace(placeId) {
                    return this.placeHours[placeId] || [];
                },
                placeHasSegment(placeId, segmentKey) {
                    const seg = this.segments[segmentKey];
                    const hours = this.hoursForPlace(placeId);
                    return hours.some(h => {
                        const [hh, mm] = h.split(':').map(v => parseInt(v, 10));
                        const minutes = (hh * 60) + (mm || 0);
                        return minutes >= seg.startMin && (minutes + 60) <= seg.endMin;
                    });
                },
                selectPlace(placeId) {
                    this.selectedPlace = placeId.toString();
                },
                availableHours() {
                    if (!this.selectedPlace) return [];
                    return this.placeHours[this.selectedPlace] || [];
                }
            }" x-init="init()">

                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm relative overflow-hidden group">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-lg shadow-indigo-100">1</div>
                        <h3 class="font-black text-gray-900 uppercase tracking-tight text-[11px]">Date</h3>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Choisissez la date de reservation.</p>
                    <input type="date" x-model="selectedDate" @change="applyDate()" min="{{ $minDate }}" max="{{ $maxDate }}" 
                        class="w-full rounded-xl border-gray-100 bg-gray-50 font-bold text-gray-700 focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-600 p-3 text-sm transition-all outline-none">
                    <button type="button" @click="setToday()" class="mt-3 w-full rounded-xl border border-gray-200 bg-white py-2 text-[10px] font-black uppercase tracking-wider text-gray-500 hover:bg-gray-50 transition-all">
                        Aujourd'hui
                    </button>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-lg shadow-indigo-100">2</div>
                        <h3 class="font-black text-gray-900 uppercase tracking-tight text-[11px]">Site</h3>
                    </div>
                    <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide no-scrollbar -mx-1 px-1">
                        <button type="button" @click="setSite('')" 
                            :class="!selectedSite ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'" 
                            class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider whitespace-nowrap transition-all">
                            Tous
                        </button>
                        @foreach($sites as $site)
                            <button type="button" @click="setSite('{{ $site->id }}')" 
                                :class="selectedSite == '{{ $site->id }}' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'" 
                                class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider whitespace-nowrap transition-all">
                                {{ $site->nom }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-lg shadow-indigo-100">3</div>
                        <h3 class="font-black text-gray-900 uppercase tracking-tight text-[11px]">Moment</h3>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Selectionnez le moment qui vous arrange.</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <button type="button" @click="setSegment('nuit')" 
                            :class="selectedSegment === 'nuit' ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" 
                            class="flex flex-col items-center justify-center gap-1 py-3 rounded-xl text-[10px] font-black transition-all uppercase">
                            Nuit
                            <span class="text-[9px] font-semibold normal-case tracking-normal text-gray-400" :class="selectedSegment === 'nuit' ? 'text-indigo-100' : 'text-gray-400'">00:00-07:30</span>
                        </button>
                        <button type="button" @click="setSegment('matin_travail')" 
                            :class="selectedSegment === 'matin_travail' ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" 
                            class="flex flex-col items-center justify-center gap-1 py-3 rounded-xl text-[10px] font-black transition-all uppercase">
                            Matin
                            <span class="text-[9px] font-semibold normal-case tracking-normal text-gray-400" :class="selectedSegment === 'matin_travail' ? 'text-indigo-100' : 'text-gray-400'">08:00-12:00</span>
                        </button>
                        <button type="button" @click="setSegment('aprem_travail')" 
                            :class="selectedSegment === 'aprem_travail' ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" 
                            class="flex flex-col items-center justify-center gap-1 py-3 rounded-xl text-[10px] font-black transition-all uppercase">
                            Aprem
                            <span class="text-[9px] font-semibold normal-case tracking-normal text-gray-400" :class="selectedSegment === 'aprem_travail' ? 'text-indigo-100' : 'text-gray-400'">12:00-17:30</span>
                        </button>
                        <button type="button" @click="setSegment('soir')" 
                            :class="selectedSegment === 'soir' ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-700 hover:bg-gray-100'" 
                            class="flex flex-col items-center justify-center gap-1 py-3 rounded-xl text-[10px] font-black transition-all uppercase">
                            Soir
                            <span class="text-[9px] font-semibold normal-case tracking-normal text-gray-400" :class="selectedSegment === 'soir' ? 'text-indigo-100' : 'text-gray-400'">18:00-23:59</span>
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-lg shadow-indigo-100">4</div>
                        <h3 class="font-black text-gray-900 uppercase tracking-tight text-[11px]">Place</h3>
                    </div>
                    <p class="text-xs text-gray-500 mb-2">Choisissez une place disponible pour ce moment.</p>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="place in filteredPlaces()" :key="place.id">
                            <button type="button" @click="selectPlace(place.id)" 
                                class="w-full text-left group relative rounded-xl border-2 p-3 transition-all duration-200" 
                                :class="selectedPlace == place.id ? 'border-indigo-600 bg-indigo-50' : 'border-gray-50 bg-white hover:border-indigo-100'">
                                
                                <div class="flex flex-col relative z-0">
                                    <div class="font-black text-sm text-gray-900 tracking-tight flex items-center justify-between">
                                        <span x-text="place.nom"></span>
                                        <template x-if="selectedPlace == place.id">
                                            <div class="w-4 h-4 rounded-full bg-indigo-600 flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex items-center justify-between mt-1">
                                        <div class="text-[10px] font-black text-indigo-600" x-text="'CHF ' + Number(place.hourly_price).toFixed(2) + '/h'"></div>
                                        <!-- Tag du site à droite -->
                                        <template x-if="!selectedSite">
                                            <span class="px-1.5 py-0.5 bg-indigo-600 text-white text-[7px] font-black rounded uppercase tracking-tighter" x-text="place.site_nom.substring(0, 4)"></span>
                                        </template>
                                    </div>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <div x-show="selectedPlace" 
                     class="bg-white border-[3px] border-indigo-600 rounded-[2rem] p-5 shadow-xl relative mt-4 mb-8"
                     x-transition:enter="transition ease-out duration-200">
                    
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-indigo-600 p-2 rounded-xl">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <h3 class="font-black text-lg text-gray-900 uppercase tracking-tighter">Récapitulatif</h3>
                    </div>

                    <div class="space-y-3 bg-gray-50 rounded-2xl p-4 mb-5 border border-gray-100">
                        <div class="flex justify-between items-center text-xs">
                            <span class="font-black text-gray-400 uppercase tracking-widest">Place</span>
                            <span class="font-black text-indigo-700" x-text="places.find(p => String(p.id) === String(selectedPlace))?.nom"></span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-200 text-xs">
                            <span class="font-black text-gray-400 uppercase tracking-widest">Moment</span>
                            <span class="font-black text-gray-900 uppercase" x-text="segments[selectedSegment].label"></span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-gray-200 text-xs">
                            <span class="font-black text-gray-400 uppercase tracking-widest">Plage</span>
                            <span class="font-black text-gray-900" x-text="segments[selectedSegment].hours"></span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('reservations.store') }}">
                        @csrf
                        <input type="hidden" name="place_id" :value="selectedPlace">
                        <input type="hidden" name="date" :value="selectedDate">
                        <input type="hidden" name="segment" :value="selectedSegment">
                        <input type="hidden" name="battement" value="5">
                        <button type="submit" class="w-full py-4 rounded-2xl font-black text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-100 active:scale-95 transform uppercase tracking-widest text-sm">
                            Confirmer & Payer
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
