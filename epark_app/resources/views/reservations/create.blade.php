<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-colors shadow-sm text-gray-600 hover:text-indigo-600 group">
                    <svg class="w-5 h-5 transition-transform group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                    <div class="p-2 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-xl text-indigo-600">
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

    <script>
        function reservationApp() {
            return {
                selectedDate: '{{ $selectedDate ?? $today }}',
                todayDate: '{{ $today }}',
                maxDate: '{{ $maxDate }}',
                currentMonth: new Date().getMonth(),
                currentYear: new Date().getFullYear(),
                selectedSite: @json($selectedSiteId ?? ''),
                selectedPlace: @json($selectedPlaceId ?? ''),
                selectedSegment: 'matin_travail',
                places: @json($placesData),
                placeHours: @json($placeHours),
                isLoading: false,
                
                segments: {
                    nuit: { label: 'Nuit', startMin: 0, endMin: 450, hours: '00:00-07:30', icon: '\ud83c\udf19' },
                    matin_travail: { label: 'Matin', startMin: 480, endMin: 720, hours: '08:00-12:00', icon: '\ud83c\udf05' },
                    aprem_travail: { label: 'Apr\u00e8s-midi', startMin: 720, endMin: 1050, hours: '12:00-17:30', icon: '\u2600\ufe0f' },
                    soir: { label: 'Soir', startMin: 1080, endMin: 1440, hours: '18:00-23:59', icon: '\ud83c\udf06' }
                },

                stepLabels: { 1: 'Date', 2: 'Site', 3: 'Cr\u00e9neau', 4: 'Place' },
                monthNames: ['Janvier', 'F\u00e9vrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Ao\u00fbt', 'Septembre', 'Octobre', 'Novembre', 'D\u00e9cembre'],
                dayNames: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            
                init() {
                    var selDate = new Date(this.selectedDate);
                    this.currentMonth = selDate.getMonth();
                    this.currentYear = selDate.getFullYear();
                    this.autoSelectPlace();
                    
                    this.$watch('selectedSegment', function() {
                        this.isLoading = true;
                        var self = this;
                        setTimeout(function() {
                            self.isLoading = false;
                            self.autoSelectPlace();
                        }, 300);
                    }.bind(this));
                    
                    this.$watch('selectedSite', function() { this.autoSelectPlace(); }.bind(this));
                },
                
                autoSelectPlace() {
                    var list = this.filteredPlaces();
                    if (!this.selectedPlace && list.length) {
                        this.selectPlace(list[0].id);
                    } else if (this.selectedPlace && !list.some(function(p) { return String(p.id) === String(this.selectedPlace); }.bind(this))) {
                        if (list.length) this.selectPlace(list[0].id);
                        else this.selectedPlace = '';
                    }
                },
            
                getCalendarDays() {
                    var days = [];
                    var firstDay = new Date(this.currentYear, this.currentMonth, 1);
                    var lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
                    var startPadding = (firstDay.getDay() + 6) % 7;
            
                    for (var i = 0; i < startPadding; i++) {
                        days.push({ day: null, disabled: true, isWeekend: false });
                    }
            
                    for (var day = 1; day <= lastDay.getDate(); day++) {
                        var m = String(this.currentMonth + 1).padStart(2, '0');
                        var d = String(day).padStart(2, '0');
                        var dateStr = this.currentYear + '-' + m + '-' + d;
                        var today = new Date();
                        today.setHours(0,0,0,0);
                        var dateObj = new Date(this.currentYear, this.currentMonth, day);
                        var maxD = new Date(this.maxDate);
                        maxD.setHours(0,0,0,0);
                        var dayOfWeek = dateObj.getDay();

                        var isDisabled = dateObj < today || dateObj > maxD;
                        var isToday = dateStr === this.todayDate;
                        var isSelected = dateStr === this.selectedDate;
                        var isWeekend = dayOfWeek === 0 || dayOfWeek === 6;
            
                        days.push({ day: day, date: dateStr, disabled: isDisabled, isToday: isToday, isSelected: isSelected, isWeekend: isWeekend });
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
                    var today = new Date();
                    var current = new Date(this.currentYear, this.currentMonth, 1);
                    return current > new Date(today.getFullYear(), today.getMonth(), 1);
                },
            
                canGoNext() {
                    var max = new Date(this.maxDate);
                    var current = new Date(this.currentYear, this.currentMonth, 1);
                    return current < new Date(max.getFullYear(), max.getMonth(), 1);
                },
            
                selectDate(date) {
                    if (!date) return;
                    this.selectedDate = date;
                    var dd = new Date(date);
                    this.currentMonth = dd.getMonth();
                    this.currentYear = dd.getFullYear();
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
                    var list = this.places;
                    if (this.selectedSite) {
                        list = list.filter(function(p) { return String(p.site_id || '') === String(this.selectedSite); }.bind(this));
                    }
                    return list.filter(function(p) { return this.placeHasSegment(p.id, this.selectedSegment); }.bind(this));
                },
            
                hoursForPlace(placeId) {
                    return this.placeHours[placeId] || [];
                },
            
                placeHasSegment(placeId, segmentKey) {
                    var seg = this.segments[segmentKey];
                    var hours = this.hoursForPlace(placeId);
                    if (!hours.length) return false;
                    
                    return hours.some(function(h) {
                        var parts = h.split(':');
                        var hh = parseInt(parts[0], 10);
                        var mm = parseInt(parts[1] || '0', 10);
                        var minutes = (hh * 60) + mm;
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
                    return this.places.find(function(p) { return String(p.id) === String(this.selectedPlace); }.bind(this));
                },
            
                get totalPrice() {
                    var place = this.selectedPlaceData;
                    if (!place) return 0;
                    var hours = { nuit: 7.5, matin_travail: 4, aprem_travail: 5.5, soir: 6 };
                    return Math.round(place.hourly_price * hours[this.selectedSegment] * 100) / 100;
                },
            
                formatDateDisplay() {
                    var date = new Date(this.selectedDate);
                    return date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
                },

                isStepCompleted(step) {
                    if (step === 1) return Boolean(this.selectedDate);
                    if (step === 2) return Boolean(this.selectedSite);
                    if (step === 3) return Boolean(this.selectedSegment);
                    if (step === 4) return Boolean(this.selectedPlace);
                    return false;
                },

                currentStep() {
                    if (!this.selectedDate) return 1;
                    if (!this.selectedSite) return 2;
                    if (!this.selectedSegment) return 3;
                    if (!this.selectedPlace) return 4;
                    return 4;
                }
            };
        }
    </script>

    <div class="py-6 bg-gray-50 min-h-screen" x-data="reservationApp()">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Progress Indicator -->
            <div class="mb-6 bg-white rounded-2xl border border-gray-100 shadow-sm p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-wide">Réservation en 4 étapes</h3>
                        <p class="text-xs text-gray-500 mt-1">Complétez chaque étape pour finaliser votre réservation</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <template x-for="step in [1, 2, 3, 4]" :key="step">
                            <div class="flex items-center">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300"
                                    :class="{
                                        'bg-emerald-500 text-white shadow-lg shadow-emerald-200': isStepCompleted(step),
                                        'bg-indigo-600 text-white ring-4 ring-indigo-100 scale-110': currentStep() === step && !isStepCompleted(step),
                                        'bg-gray-100 text-gray-400': !isStepCompleted(step) && currentStep() !== step
                                    }"
                                    x-text="isStepCompleted(step) ? '✓' : step">
                                </span>
                                <span class="hidden sm:block ml-2 text-xs font-semibold transition-colors"
                                    :class="currentStep() === step ? 'text-indigo-600' : (isStepCompleted(step) ? 'text-emerald-600' : 'text-gray-400')"
                                    x-text="stepLabels[step]">
                                </span>
                                <div x-show="step < 4" class="w-8 h-0.5 mx-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 transition-all duration-500" 
                                        :style="`width: ${isStepCompleted(step) ? '100%' : '0%'}`"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                <!-- Colonne principale -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Info Groupes -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-start gap-4">
                            <div class="shrink-0 w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-gray-900">Accès privé automatique</p>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">Si vous faites partie d'un groupe, les places privées apparaissent automatiquement dans la liste.</p>
                                
                                @if(!empty($savedGroupEntries ?? []))
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @foreach($savedGroupEntries as $entry)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold border border-indigo-100">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ $entry['name'] ?: 'Groupe sans nom' }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2 border border-dashed border-gray-200">
                                        <span>Aucun groupe connecté</span>
                                        <a href="{{ route('profile.edit') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 hover:underline">Ajouter un groupe →</a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
            
                    @if ($errors->any())
                        <div class="rounded-2xl border-l-4 border-red-500 bg-red-50 p-4 shadow-sm animate-slide-up">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <h4 class="text-sm font-bold text-red-800">Erreur de validation</h4>
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
                    <div class="bg-white rounded-lg p-5 sm:p-6 shadow-sm border border-gray-100 transition-all duration-300"
                        :class="currentStep() === 1 ? 'ring-2 ring-indigo-500 ring-offset-2' : ''">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-1">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Date de réservation</h3>
                                    <p class="text-xs text-gray-500">Sélectionnez votre jour</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="prevMonth()" :disabled="!canGoPrev()" 
                                    class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-all hover:scale-110 text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>
                                <div class="text-center min-w-[140px]">
                                    <div class="font-bold text-gray-900 text-lg" x-text="monthNames[currentMonth] + ' ' + currentYear"></div>
                                    <div class="text-[11px] text-gray-400">Jusqu'au <span class="text-indigo-600 font-semibold" x-text="new Date(maxDate).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })"></span></div>
                                </div>
                                <button @click="nextMonth()" :disabled="!canGoNext()"
                                    class="p-2 rounded-lg hover:bg-gray-100 disabled:opacity-30 disabled:cursor-not-allowed transition-all hover:scale-110 text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Jours de la semaine -->
                        <div class="grid grid-cols-7 gap-1 mb-2">
                            <template x-for="(day, dayIndex) in dayNames" :key="day">
                                <div class="text-center text-[11px] font-bold uppercase tracking-wider py-2"
                                    :class="dayIndex >= 5 ? 'text-indigo-400' : 'text-gray-400'"
                                    x-text="day"></div>
                            </template>
                        </div>

                        <!-- Grille des jours -->
                        <div class="grid grid-cols-7 gap-2">
                            <template x-for="(dayInfo, index) in getCalendarDays()" :key="index">
                                <button @click="!dayInfo.disabled && selectDate(dayInfo.date)"
                                    :disabled="dayInfo.disabled"
                                    class="relative h-10 w-10 mx-auto rounded-xl text-xs font-semibold transition-all duration-200 flex items-center justify-center"
                                    :class="{
                                        'bg-indigo-600 text-white shadow-lg shadow-indigo-200 scale-105 ring-2 ring-indigo-600 ring-offset-2': dayInfo.isSelected,
                                        'bg-indigo-50 text-indigo-700 border-2 border-indigo-200': dayInfo.isToday && !dayInfo.isSelected,
                                        'invisible': !dayInfo.day,
                                        'text-gray-300 cursor-not-allowed bg-gray-50': dayInfo.disabled && dayInfo.day,
                                        'text-indigo-700 bg-indigo-50/50': dayInfo.isWeekend && !dayInfo.disabled && !dayInfo.isSelected && !dayInfo.isToday,
                                        'text-gray-700 hover:bg-gray-100 hover:shadow-md': !dayInfo.disabled && dayInfo.day && !dayInfo.isSelected && !dayInfo.isToday
                                    }">
                                    <span x-show="dayInfo.day" x-text="dayInfo.day"></span>
                                    <span x-show="dayInfo.isToday && !dayInfo.isSelected" class="absolute bottom-1.5 w-1.5 h-1.5 bg-indigo-600 rounded-full"></span>
                                </button>
                            </template>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <button @click="setToday()" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center gap-1.5 transition-colors bg-indigo-50 px-3 py-1.5 rounded-lg hover:bg-indigo-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg> 
                                Aujourd'hui
                            </button>
                            <div class="flex gap-4 text-[11px] text-gray-500 font-medium">
                                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-indigo-50 border-2 border-indigo-200"></span> Aujourd'hui</div>
                                <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-indigo-600 shadow-sm"></span> Sélectionné</div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: SITE -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-100 shadow-sm transition-all duration-300"
                        :class="currentStep() === 2 ? 'ring-2 ring-indigo-500 ring-offset-2' : ''">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Site de parking</h3>
                                <p class="text-xs text-gray-500">Filtrer par emplacement</p>
                            </div>
                        </div>

                        <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                            <button @click="setSite('')"
                                :class="!selectedSite ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                class="px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition-all duration-200 flex items-center gap-2">
                                <svg x-show="!selectedSite" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Tous les sites
                            </button>
                            @foreach ($sites as $site)
                                <button @click="setSite('{{ $site->id }}')"
                                    :class="selectedSite == '{{ $site->id }}' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                                    class="px-5 py-2.5 rounded-xl text-sm font-bold whitespace-nowrap transition-all duration-200">
                                    {{ $site->nom }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- STEP 3: CRÉNEAU -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-100 shadow-sm transition-all duration-300"
                        :class="currentStep() === 3 ? 'ring-2 ring-indigo-500 ring-offset-2' : ''">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Créneau horaire</h3>
                                <p class="text-xs text-gray-500">Choisissez votre plage horaire</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                            <template x-for="(segment, key) in segments" :key="key">
                                <button @click="setSegment(key)"
                                    :class="selectedSegment === key ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-200 scale-[1.02]' : 'bg-white text-gray-700 hover:shadow-lg border-2 border-gray-100 hover:border-indigo-200'"
                                    class="relative p-4 rounded-xl transition-all duration-200 group text-left">
                                    <div class="flex items-start justify-between mb-2">
                                        <span class="text-2xl filter drop-shadow-sm group-hover:scale-110 transition-transform duration-200" x-text="segment.icon"></span>
                                        <div x-show="selectedSegment === key" class="w-5 h-5 rounded-full bg-white/20 flex items-center justify-center">
                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="text-sm font-bold" x-text="segment.label"></div>
                                    <div class="text-xs mt-0.5 opacity-80" x-text="segment.hours"></div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- STEP 4: PLACES -->
                    <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-100 shadow-sm transition-all duration-300"
                        :class="currentStep() === 4 ? 'ring-2 ring-indigo-500 ring-offset-2' : ''">
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900">Place disponible</h3>
                                    <p class="text-xs text-gray-500">
                                        <span x-show="filteredPlaces().length > 0" class="text-emerald-600 font-semibold" x-text="filteredPlaces().length + ' place(s) trouvée(s)'"></span>
                                        <span x-show="filteredPlaces().length === 0" class="text-red-500 font-semibold">Aucune place disponible</span>
                                    </p>
                                </div>
                            </div>
                            <div x-show="isLoading" class="flex items-center gap-2 text-sm text-indigo-600">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-xs font-medium">Chargement...</span>
                            </div>
                        </div>

                        <div x-show="!isLoading && filteredPlaces().length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <template x-for="place in filteredPlaces()" :key="place.id">
                                <button @click="selectPlace(place.id)"
                                    :class="selectedPlace == place.id ? 'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-600 ring-offset-2' : 'border-gray-200 bg-white hover:border-indigo-300 hover:shadow-lg'"
                                    class="relative text-left rounded-xl border-2 p-4 transition-all duration-200 group">
                                    
                                    <div class="flex items-start justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="font-bold text-gray-900 truncate" x-text="place.nom"></div>
                                            <div class="text-xs text-gray-500 mt-1 truncate" x-text="place.adresse || 'Adresse non spécifiée'"></div>
                                        </div>
                                        <div x-show="selectedPlace == place.id" 
                                            class="w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center text-white shadow-md shrink-0 ml-2">
                                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex items-center justify-between pt-3 border-t border-gray-100">
                                        <div class="text-lg font-black text-indigo-600" x-text="new Intl.NumberFormat('fr-CH', { style: 'currency', currency: 'CHF' }).format(place.hourly_price) + '/h'"></div>
                                        <span x-show="!selectedSite" class="text-[10px] font-bold px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg" x-text="place.site_nom"></span>
                                    </div>
                                </button>
                            </template>
                        </div>

                        <div x-show="!isLoading && filteredPlaces().length === 0" class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-gray-500 font-medium">Aucune place disponible pour ce créneau</p>
                            <p class="text-sm text-gray-400 mt-1">Essayez un autre horaire ou site</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar récapitulatif -->
                <div class="lg:col-span-1">
                    <div class="lg:sticky lg:top-24 space-y-4">
                        <!-- Récapitulatif -->
                        <div x-show="selectedPlace && filteredPlaces().length > 0"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-4"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="bg-white rounded-2xl shadow-xl border border-indigo-100 overflow-hidden">
                            
                            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                                <h3 class="font-bold text-white text-lg flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    Récapitulatif
                                </h3>
                            </div>

                            <div class="p-6 space-y-4">
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-500">Place</span>
                                        <span class="font-semibold text-gray-900 text-right" x-text="selectedPlaceData ? selectedPlaceData.nom : ''"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-500">Date</span>
                                        <span class="font-semibold text-gray-900 capitalize" x-text="formatDateDisplay()"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-sm">
                                        <span class="text-gray-500">Horaire</span>
                                        <span class="font-semibold text-indigo-600" x-text="currentSegmentData ? (currentSegmentData.label + ' (' + currentSegmentData.hours + ')') : ''"></span>
                                    </div>
                                    <div class="h-px bg-gray-100 my-3"></div>
                                    <div class="flex justify-between items-end">
                                        <span class="text-gray-900 font-bold">Total</span>
                                        <span class="text-3xl font-black text-indigo-600" x-text="new Intl.NumberFormat('fr-CH', { style: 'currency', currency: 'CHF' }).format(totalPrice)"></span>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('reservations.store') }}" class="pt-2">
                                    @csrf
                                    <input type="hidden" name="place_id" :value="selectedPlace">
                                    <input type="hidden" name="date" :value="selectedDate">
                                    <input type="hidden" name="segment" :value="selectedSegment">
                                    <input type="hidden" name="battement" value="5">

                                    <button type="submit" class="w-full py-4 rounded-xl font-bold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg shadow-indigo-200 hover:shadow-xl active:scale-[0.98] flex items-center justify-center gap-2 group">
                                        <span>Confirmer</span>
                                        <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                        </svg>
                                    </button>
                                </form>
                                
                                <div class="flex items-center justify-center gap-1.5 text-[11px] text-gray-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg> 
                                    Paiement sécurisé Stripe
                                </div>
                            </div>
                        </div>

                        <!-- État vide -->
                        <div x-show="!selectedPlace || filteredPlaces().length === 0" 
                            class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-6 text-center">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 font-medium">Sélectionnez une place pour voir le récapitulatif</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    
    <style>
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        .animate-slide-up { animation: slideUp 0.3s ease-out; }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</x-app-layout>