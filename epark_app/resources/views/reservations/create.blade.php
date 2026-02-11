<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-colors shadow-sm text-gray-600">
                    <!-- SVG Arrow Left -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                    <div class="p-2 bg-indigo-100 rounded-xl text-indigo-600">
                        <!-- SVG Calendar -->
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    Réserver une place
                </h2>
            </div>
        </div>
    </x-slot>

    @php
        $placesData = $places->map(function ($place) {
            return [
                'id' => $place->id,
                'nom' => $place->nom,
                'adresse' => optional($place->site)->adresse,
                'site_id' => optional($place->site)->id,
                'site_nom' => optional($place->site)->nom,
                'hourly_price' => $place->hourly_price_cents ? $place->hourly_price_cents / 100 : 0,
            ];
        });

        $today = now()->format('Y-m-d');
        $maxDate = now()->addWeeks(3)->format('Y-m-d');
    @endphp

    <div class="py-6 bg-gray-50 min-h-screen" x-data="{
        selectedDate: '{{ $selectedDate ?? $today }}',
        todayDate: '{{ $today }}',
        maxDate: '{{ $maxDate }}',
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        selectedSite: {{ json_encode($selectedSiteId ?? '') }},
        selectedPlace: {{ json_encode($selectedPlaceId ?? '') }},
        selectedSegment: 'matin_travail',
        paymentDone: false,
        places: {{ json_encode($placesData) }},
        placeHours: {{ json_encode($placeHours) }},
        isLoading: false,
        
        segments: {
            nuit: { label: 'Nuit', startMin: 0, endMin: 450, hours: '00:00-07:30', icon: '🌙' },
            matin_travail: { label: 'Matin', startMin: 480, endMin: 720, hours: '08:00-12:00', icon: '🌅' },
            aprem_travail: { label: 'Après-midi', startMin: 720, endMin: 1050, hours: '12:00-17:30', icon: '☀️' },
            soir: { label: 'Soir', startMin: 1080, endMin: 1440, hours: '18:00-23:59', icon: '🌆' }
        },
        
        monthNames: ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
        dayNames: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
    
        init() {
            const selDate = new Date(this.selectedDate);
            this.currentMonth = selDate.getMonth();
            this.currentYear = selDate.getFullYear();
    
            this.autoSelectPlace();
            
            this.$watch('selectedSegment', () => {
                this.isLoading = true;
                setTimeout(() => {
                    this.isLoading = false;
                    this.autoSelectPlace();
                }, 300);
            });
            
            this.$watch('selectedSite', () => this.autoSelectPlace());
        },
        
        autoSelectPlace() {
            const list = this.filteredPlaces();
            if (!this.selectedPlace && list.length) {
                this.selectPlace(list[0].id);
            } else if (this.selectedPlace && !list.some(p => String(p.id) === String(this.selectedPlace))) {
                 if (list.length) this.selectPlace(list[0].id);
                 else this.selectedPlace = '';
            }
        },
    
        getCalendarDays() {
            const days = [];
            const firstDay = new Date(this.currentYear, this.currentMonth, 1);
            const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
            const startPadding = firstDay.getDay(); 
    
            for (let i = 0; i < startPadding; i++) {
                days.push({ day: null, disabled: true });
            }
    
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const dateStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                
                const today = new Date();
                today.setHours(0,0,0,0);
                
                const dateObj = new Date(this.currentYear, this.currentMonth, day);
                
                const maxD = new Date(this.maxDate);
                maxD.setHours(0,0,0,0);

                const isDisabled = dateObj < today || dateObj > maxD;
                const isToday = dateStr === this.todayDate;
                const isSelected = dateStr === this.selectedDate;
    
                days.push({ day, date: dateStr, disabled: isDisabled, isToday, isSelected });
            }
            return days;
        },
    
        prevMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
        },
    
        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
        },
    
        canGoPrev() {
            const today = new Date();
            const current = new Date(this.currentYear, this.currentMonth, 1);
            return current > new Date(today.getFullYear(), today.getMonth(), 1);
        },
    
        canGoNext() {
            const max = new Date(this.maxDate);
            const current = new Date(this.currentYear, this.currentMonth, 1);
            return current < new Date(max.getFullYear(), max.getMonth(), 1);
        },
    
        selectDate(date) {
            if (!date) return;
            this.selectedDate = date;
            const d = new Date(date);
            this.currentMonth = d.getMonth();
            this.currentYear = d.getFullYear();
        },
    
        setToday() {
            this.selectDate(this.todayDate);
        },
    
        setSite(siteId) {
            this.selectedSite = siteId;
            this.selectedPlace = ''; 
            this.autoSelectPlace();
        },
    
        setSegment(segmentKey) {
            this.selectedSegment = segmentKey;
            this.autoSelectPlace();
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
            if (!hours.length) return false;
            
            return hours.some(h => {
                const [hh, mm] = h.split(':').map(v => parseInt(v, 10));
                const minutes = (hh * 60) + (mm || 0);
                return minutes >= seg.startMin && (minutes + 60) <= seg.endMin;
            });
        },
    
        selectPlace(placeId) {
            this.selectedPlace = placeId.toString();
        },
    
        get currentSegmentData() {
            return this.segments[this.selectedSegment];
        },
    
        get selectedPlaceData() {
            return this.places.find(p => String(p.id) === String(this.selectedPlace));
        },
    
        get totalPrice() {
            const place = this.selectedPlaceData;
            if (!place) return 0;
            const hours = { nuit: 7.5, matin_travail: 4, aprem_travail: 5.5, soir: 6 };
            const total = place.hourly_price * hours[this.selectedSegment];
            return total.toFixed(2);
        },
    
        formatDateDisplay() {
            const date = new Date(this.selectedDate);
            return date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
        }
    }">

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            @if ($errors->any())
                <div class="rounded-2xl border-l-4 border-red-500 bg-red-50 p-4 shadow-sm animate-slide-up">
                    <div class="flex items-start gap-3">
                        <!-- SVG Exclamation -->
                        <svg class="w-5 h-5 text-red-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div>
                            <h4 class="text-sm font-bold text-red-800">Erreur</h4>
                            <ul class="text-sm text-red-700 mt-1 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- CALENDRIER -->
            <div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-gray-100 overflow-hidden">
                <!-- Navigation -->
                <div class="flex items-center justify-between mb-6">
                    <button @click="prevMonth()" :disabled="!canGoPrev()" 
                        class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors text-gray-600">
                        <!-- SVG Chevron Left -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div class="text-center">
                        <div class="font-bold text-gray-900 text-lg" x-text="monthNames[currentMonth] + ' ' + currentYear"></div>
                        <div class="text-xs text-gray-500 mt-1">
                            Max: <span class="text-indigo-600 font-semibold" x-text="new Date(maxDate).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })"></span>
                        </div>
                    </div>
                    <button @click="nextMonth()" :disabled="!canGoNext()"
                        class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-colors text-gray-600">
                        <!-- SVG Chevron Right -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <!-- En-tête Jours (DIM LUN MAR...) -->
                <!-- Correction : Ajout de style inline pour forcer la grille -->
                <div class="grid grid-cols-7 gap-1 mb-2" style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr));">
                    <template x-for="day in dayNames" :key="day">
                        <div class="text-center text-[10px] font-bold text-gray-400 uppercase tracking-wider" x-text="day"></div>
                    </template>
                </div>

                <!-- Grille Jours (Les boutons) -->
                <!-- Correction : Ajout de style inline pour forcer la grille -->
                <div class="grid grid-cols-7 gap-1 sm:gap-2" style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr));">
                    <template x-for="(dayInfo, index) in getCalendarDays()" :key="index">
                        <button @click="!dayInfo.disabled && selectDate(dayInfo.date)"
                            :disabled="dayInfo.disabled"
                            :aria-label="dayInfo.date ? dayInfo.date + ' ' + (dayInfo.disabled ? 'Indisponible' : 'Réserver') : ''"
                            class="relative aspect-square rounded-lg sm:rounded-xl text-sm font-medium transition-all duration-200 flex flex-col items-center justify-center"
                            :class="{
                                'bg-indigo-600 text-white shadow-lg shadow-indigo-200 transform scale-105': dayInfo.isSelected,
                                'bg-indigo-50 text-indigo-700 border border-indigo-100': dayInfo.isToday && !dayInfo.isSelected,
                                'text-gray-300 cursor-default bg-transparent': !dayInfo.day,
                                'text-gray-300 cursor-not-allowed': dayInfo.disabled && dayInfo.day,
                                'text-gray-700 hover:bg-gray-100 hover:shadow-sm': !dayInfo.disabled && dayInfo.day && !dayInfo.isSelected && !dayInfo.isToday
                            }">
                            <span x-show="dayInfo.day" x-text="dayInfo.day"></span>
                            <span x-show="dayInfo.isToday && !dayInfo.isSelected" class="absolute bottom-2 w-1 h-1 bg-indigo-600 rounded-full"></span>
                        </button>
                    </template>
                </div>

                <!-- Footer Calendrier -->
                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <button @click="setToday()" class="text-xs font-semibold text-gray-600 hover:text-indigo-600 flex items-center gap-1 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg> 
                        Aujourd'hui
                    </button>
                    <div class="flex gap-3 text-[10px] text-gray-500 font-medium">
                        <div class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-indigo-50 border border-indigo-200"></span>Auj</div>
                        <div class="flex items-center gap-1"><span class="w-2 h-2 rounded bg-indigo-600"></span>Sél</div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: SITE -->
            <div class="step-card bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <!-- SVG Building -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wide">Site</h3>
                        <p class="text-xs text-gray-500">Choisissez votre emplacement</p>
                    </div>
                </div>

                <div class="flex gap-2 overflow-x-auto pb-2 no-scrollbar -mx-2 px-2">
                    <button @click="setSite('')"
                        :class="!selectedSite ? 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-600 ring-offset-1' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all duration-200">
                        Tous les sites
                    </button>
                    @foreach ($sites as $site)
                        <button @click="setSite('{{ $site->id }}')"
                            :class="selectedSite == '{{ $site->id }}' ? 'bg-indigo-600 text-white shadow-md ring-2 ring-indigo-600 ring-offset-1' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold whitespace-nowrap transition-all duration-200">
                            {{ $site->nom }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- STEP 3: MOMENT -->
            <div class="step-card bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <!-- SVG Clock -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wide">Créneau</h3>
                        <p class="text-xs text-gray-500">Matin, après-midi ou soir ?</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <template x-for="(segment, key) in segments" :key="key">
                        <button @click="setSegment(key)"
                            :class="selectedSegment === key ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200 ring-2 ring-indigo-500 ring-offset-2' : 'bg-gray-50 text-gray-700 hover:bg-white hover:shadow-md border border-gray-100'"
                            class="flex items-center justify-start gap-2.5 py-3 px-3 rounded-xl transition-all duration-200 group">
                            <span class="text-xl filter drop-shadow-sm group-hover:scale-110 transition-transform" x-text="segment.icon"></span>
                            <div class="text-left leading-tight">
                                <div class="text-[11px] font-bold uppercase tracking-wider" x-text="segment.label"></div>
                                <div class="text-[10px] font-medium mt-0.5 opacity-70" x-text="segment.hours"></div>
                            </div>
                        </button>
                    </template>
                </div>
            </div>

            <!-- STEP 4: PLACES -->
            <div class="step-card bg-white rounded-2xl p-5 border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <!-- SVG Map Pin -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wide">Place</h3>
                            <p class="text-xs text-gray-500">
                                <span x-show="filteredPlaces().length" class="text-indigo-600 font-medium" x-text="filteredPlaces().length + ' disponible(s)'"></span>
                                <span x-show="!filteredPlaces().length" class="text-red-500 font-medium">Aucune place disponible</span>
                            </p>
                        </div>
                    </div>
                </div>

                <div x-show="isLoading" class="flex justify-center py-6" style="display: none;">
                    <svg class="animate-spin h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <div x-show="!isLoading" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <template x-for="place in filteredPlaces()" :key="place.id">
                        <button @click="selectPlace(place.id)"
                            :class="selectedPlace == place.id ? 'border-indigo-600 bg-indigo-50/50 ring-1 ring-indigo-600' : 'border-gray-200 bg-white hover:border-indigo-300 hover:shadow-sm'"
                            class="relative text-left rounded-xl border-2 p-4 transition-all duration-200 group">
                            
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="font-bold text-gray-900" x-text="place.nom"></div>
                                    <div class="text-xs text-gray-500 mt-1 truncate max-w-[150px]" x-text="place.adresse || 'Adresse non spécifiée'"></div>
                                </div>
                                <div x-show="selectedPlace == place.id" class="w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center text-white shadow-sm">
                                    <!-- SVG Check -->
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-2">
                                <div class="text-sm font-bold text-indigo-600" x-text="new Intl.NumberFormat('fr-CH', { style: 'currency', currency: 'CHF' }).format(place.hourly_price) + '/h'"></div>
                                <span x-show="!selectedSite" class="text-[10px] font-bold px-2 py-1 bg-gray-100 text-gray-600 rounded-lg" x-text="place.site_nom"></span>
                            </div>
                        </button>
                    </template>
                </div>

                <div x-show="!isLoading && !filteredPlaces().length" class="text-center py-8 text-gray-400" style="display: none;">
                    <!-- SVG Exclamation Circle -->
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="text-sm">Essayez un autre créneau horaire.</p>
                </div>
            </div>

            <!-- RECAP & ACTION -->
            <div x-show="selectedPlace && filteredPlaces().length"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="bg-white rounded-2xl shadow-lg border border-indigo-100 overflow-hidden ring-1 ring-black/5">
                
                <div class="bg-indigo-600 px-6 py-4 flex items-center gap-3">
                    <div class="p-1.5 bg-white/20 rounded-lg">
                        <!-- SVG Receipt -->
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 10-1 0 .5.5 0 001 0z" />
                        </svg>
                    </div>
                    <h3 class="font-bold text-white text-lg">Récapitulatif</h3>
                </div>

                <div class="p-6">
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Lieu</span>
                            <span class="font-medium text-gray-900 text-right max-w-[200px] truncate" x-text="selectedPlaceData?.nom"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Date</span>
                            <span class="font-medium text-gray-900 capitalize" x-text="formatDateDisplay()"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Horaire</span>
                            <span class="font-medium text-indigo-600" x-text="currentSegmentData.label + ' (' + currentSegmentData.hours + ')'"></span>
                        </div>
                        <div class="border-t border-dashed border-gray-200 my-2"></div>
                        <div class="flex justify-between items-end">
                            <span class="text-gray-900 font-bold">Total à payer</span>
                            <span class="text-2xl font-black text-indigo-600" x-text="new Intl.NumberFormat('fr-CH', { style: 'currency', currency: 'CHF' }).format(totalPrice)"></span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('reservations.store') }}">
                        @csrf
                        <input type="hidden" name="place_id" :value="selectedPlace">
                        <input type="hidden" name="date" :value="selectedDate">
                        <input type="hidden" name="segment" :value="selectedSegment">
                        <input type="hidden" name="battement" value="5">

                        <button type="submit" class="w-full py-4 rounded-xl font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-200 hover:shadow-xl active:scale-[0.98] flex items-center justify-center gap-2">
                            <span>Confirmer la réservation</span>
                            <!-- SVG Arrow Right -->
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </button>
                    </form>
                    
                    <p class="text-center text-gray-400 text-[10px] mt-3 flex items-center justify-center gap-1">
                        <!-- SVG Lock Closed -->
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg> 
                        Paiement sécurisé via Stripe
                    </p>
                </div>
            </div>

        </div>
    </div>
    
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .step-card { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .step-card:hover { transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
    </style>
</x-app-layout>