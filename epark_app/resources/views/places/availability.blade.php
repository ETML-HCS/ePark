<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('places.mes') }}" class="p-2 bg-white rounded-xl hover:bg-gray-50 transition-all shadow-sm">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h2 class="font-black text-2xl text-gray-900 flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-xl">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                Indisponibilités - {{ $place->nom }}
            </h2>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-gray-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            @if($errors->any())
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 rounded-xl p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-bold text-red-900 mb-2">Erreurs détectées</h3>
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700">• {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Hebdomadaires --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Règles hebdomadaires (créneaux non réservables)
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Chaque plage saisie bloque la réservation sur le créneau indiqué.</p>
                </div>
                <form method="POST" action="{{ route('places.blocked-slots.update', $place->id) }}" class="p-4 sm:p-6">
                    @csrf
                    <div class="mb-6 border border-gray-200 rounded-xl p-3 sm:p-4">
                        <h4 class="text-sm font-bold text-gray-900 mb-2">Période de validité</h4>
                        <p class="text-xs text-gray-500 mb-3">Définissez la période pendant laquelle les règles horaires s'appliquent.</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label for="availability_start_date" class="text-xs font-bold text-gray-600 mb-1 block">Début de validité</label>
                                <input id="availability_start_date" type="date" name="availability_start_date" value="{{ old('availability_start_date', optional($place->availability_start_date)->format('Y-m-d')) }}" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label for="availability_end_date" class="text-xs font-bold text-gray-600 mb-1 block">Fin de validité</label>
                                <input id="availability_end_date" type="date" name="availability_end_date" value="{{ old('availability_end_date', optional($place->availability_end_date)->format('Y-m-d')) }}" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                        </div>
                        <div id="school-semester-shortcuts" class="mt-3 hidden">
                            <p class="text-xs font-bold text-gray-600 mb-2">Raccourcis scolaires (trimestres)</p>
                            <p id="school-year-label" class="text-[11px] text-gray-500 mb-2"></p>
                            <div class="overflow-x-auto pb-1">
                                <div class="flex gap-2 min-w-max">
                                    <button type="button" class="px-3 py-1.5 text-[11px] sm:text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors whitespace-nowrap" data-trimester-shortcut="1" data-trimester-label>Trimestre 1</button>
                                    <button type="button" class="px-3 py-1.5 text-[11px] sm:text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors whitespace-nowrap" data-trimester-shortcut="2" data-trimester-label>Trimestre 2</button>
                                    <button type="button" class="px-3 py-1.5 text-[11px] sm:text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors whitespace-nowrap" data-trimester-shortcut="3" data-trimester-label>Trimestre 3</button>
                                    <button type="button" class="px-3 py-1.5 text-[11px] sm:text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors whitespace-nowrap" data-trimester-shortcut="4" data-trimester-label>Trimestre 4</button>
                                    <button type="button" class="px-3 py-1.5 text-[11px] sm:text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors whitespace-nowrap" data-trimester-shortcut="5" data-trimester-label>T5 Fin d'année</button>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-500 mt-2">Les raccourcis sont automatiquement alignés sur l'année scolaire en cours.</p>
                        </div>
                    </div>

                    <div class="mb-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h4 class="text-sm font-bold text-gray-900 mb-2">Calendrier visuel</h4>
                            <p class="text-xs text-gray-500 mb-3">Type calendrier + plage horaire d'affichage (futur agenda style Outlook).</p>
                            <div class="space-y-3">
                                <select name="weekly_schedule_type" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" data-weekly-schedule-type>
                                    <option value="">Aucun calendrier</option>
                                    <option value="full_week" @selected(($place->weekly_schedule_type ?? null) === 'full_week')>Semaine complète (Lundi → Dimanche)</option>
                                    <option value="work_week" @selected(($place->weekly_schedule_type ?? null) === 'work_week')>Semaine de travail (Lundi → Vendredi)</option>
                                </select>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label for="visual_day_start_time" class="text-xs font-bold text-gray-600 mb-1 block">Début affichage</label>
                                        <input id="visual_day_start_time" type="time" name="visual_day_start_time" value="{{ old('visual_day_start_time', $place->visual_day_start_time) }}" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" data-visual-start>
                                    </div>
                                    <div>
                                        <label for="visual_day_end_time" class="text-xs font-bold text-gray-600 mb-1 block">Fin affichage</label>
                                        <input id="visual_day_end_time" type="time" name="visual_day_end_time" value="{{ old('visual_day_end_time', $place->visual_day_end_time) }}" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" data-visual-end>
                                    </div>
                                </div>
                                <button type="button" class="w-full sm:w-auto px-3 py-2 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-apply-etml>
                                    Raccourci ETML/CFPV (8h00-17h25)
                                </button>
                            </div>
                        </div>

                        <div class="border border-gray-200 rounded-xl p-3 sm:p-4">
                            <h4 class="text-sm font-bold text-gray-900 mb-2">Réservation de groupe</h4>
                            <p class="text-xs text-gray-500 mb-3">Si activé, seuls les utilisateurs avec le code groupe verront la place.</p>

                            <input type="hidden" name="is_group_reserved" value="0">
                            <label class="inline-flex items-center gap-2 mb-3">
                                <input type="checkbox" name="is_group_reserved" value="1" @checked((bool)($place->is_group_reserved ?? false)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm font-semibold text-gray-800">Réservée à un groupe</span>
                            </label>

                            <label class="inline-flex items-center gap-2 mb-3">
                                <input type="checkbox" id="is_school_group" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" data-school-group-toggle>
                                <span class="text-sm font-semibold text-gray-800">Groupe scolaire (école)</span>
                            </label>
                            <div class="mb-3">
                                <span id="school-mode-badge" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold hidden">
                                    Mode école actif
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-500 mb-2">Le mode école n'applique pas automatiquement les indisponibilités.</p>
                            <p class="text-xs text-gray-500 mb-3">Active le preset ETML/CFPV (nom du groupe + semaine de travail + plages horaires).</p>

                            <div class="space-y-3">
                                <div>
                                    <label for="group_name" class="text-xs font-bold text-gray-600 mb-1 block">Nom du groupe</label>
                                    <input id="group_name" type="text" name="group_name" value="{{ old('group_name', $place->group_name) }}" placeholder="Ex: Equipe A" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="group_access_code" class="text-xs font-bold text-gray-600 mb-1 block">Code d'accès groupe</label>
                                    <input id="group_access_code" type="text" name="group_access_code" placeholder="Laisser vide pour conserver le code actuel" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label for="group_allowed_email_domains_raw" class="text-xs font-bold text-gray-600 mb-1 block">Domaines email autorisés (optionnel)</label>
                                    <textarea id="group_allowed_email_domains_raw" name="group_allowed_email_domains_raw" rows="2" placeholder="Ex: eduvaud.ch" class="w-full rounded-xl border-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('group_allowed_email_domains_raw', implode(PHP_EOL, $place->group_allowed_email_domains ?? [])) }}</textarea>
                                    <p class="mt-1 text-[11px] text-gray-500">Un domaine par ligne (ou séparé par virgule). Les utilisateurs connectés avec ces emails verront la place sans saisir de code.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 overflow-x-auto pb-1">
                        <div class="flex gap-2 min-w-max">
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors" data-clear-slot data-day="all">
                            ✓ Tout laisser libre
                        </button>
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-quick-slot data-day="all" data-start="00:00" data-end="23:59">
                            Tout bloquer : journée entière
                        </button>
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-quick-slot data-day="all" data-start="07:30" data-end="17:00">
                            Tout bloquer : 07:30-17:00
                        </button>
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-quick-slot data-day="all" data-start="07:30" data-end="12:00">
                            Tout bloquer : 07:30-12:00
                        </button>
                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-quick-slot data-day="all" data-start="12:00" data-end="17:30">
                            Tout bloquer : 12:00-17:30
                        </button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        @foreach($days as $dayIndex => $dayLabel)
                            @php
                                $daySlots = $weeklyBlockedSlotsByDay->get($dayIndex, collect())->values();
                                if ($daySlots->isEmpty()) {
                                    $daySlots = collect([(object) ['start_time' => null, 'end_time' => null]]);
                                }
                            @endphp
                            <div class="border-2 border-gray-200 rounded-xl p-4 hover:border-indigo-200 transition-colors">
                                <div class="font-bold text-sm text-gray-900 mb-3">{{ $dayLabel }}</div>
                                <div class="space-y-2" data-day-blocks="{{ $dayIndex }}" data-next-index="{{ $daySlots->count() }}">
                                    @foreach($daySlots as $slotIndex => $slot)
                                        <div class="flex flex-col sm:flex-row gap-2 sm:items-center" data-slot-row>
                                            <input type="time" name="weekly_blocked_slots[{{ $dayIndex }}][{{ $slotIndex }}][start]" class="flex-1 border-2 border-gray-200 bg-gray-50 rounded-xl focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all" data-role="start" data-day="{{ $dayIndex }}" value="{{ $slot->start_time ?? '' }}">
                                            <input type="time" name="weekly_blocked_slots[{{ $dayIndex }}][{{ $slotIndex }}][end]" class="flex-1 border-2 border-gray-200 bg-gray-50 rounded-xl focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all" data-role="end" data-day="{{ $dayIndex }}" value="{{ $slot->end_time ?? '' }}">
                                            <button type="button" class="px-2 py-2 text-xs font-semibold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors self-end sm:self-auto" data-remove-slot>×</button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="mt-2 px-2 py-1 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-colors" data-add-slot data-day="{{ $dayIndex }}">+ Ajouter plage</button>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-colors" data-clear-slot data-day="{{ $dayIndex }}">
                                        Libre
                                    </button>
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-quick-slot data-day="{{ $dayIndex }}" data-start="00:00" data-end="23:59">
                                        24h
                                    </button>
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-quick-slot data-day="{{ $dayIndex }}" data-start="07:30" data-end="17:00">
                                        7h30-17h
                                    </button>
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-quick-slot data-day="{{ $dayIndex }}" data-start="07:30" data-end="12:00">
                                        Matin
                                    </button>
                                    <button type="button" class="px-2 py-1 text-xs font-semibold rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors" data-quick-slot data-day="{{ $dayIndex }}" data-start="12:00" data-end="17:30">
                                        Après-midi
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @php
                        $hasAtLeastOneBlockedSlot = $weeklyBlockedSlotsByDay
                            ->flatten(1)
                            ->contains(fn($slot) => !empty($slot->start_time) && !empty($slot->end_time));
                    @endphp

                    @if(!$hasAtLeastOneBlockedSlot)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Cette place ne dispose actuellement d'aucun horaire configuré. Le propriétaire va mettre prochainement à jour.
                        </div>
                    @endif

                    <div class="mt-6">
                        <x-primary-button class="px-6 py-3">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Enregistrer les règles hebdomadaires
                        </x-primary-button>
                    </div>
                </form>
            </div>

            {{-- Exceptionnelles --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        Indisponibilités exceptionnelles
                    </h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="{{ route('places.unavailability.store', $place->id) }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        @csrf
                        <div>
                            <x-input-label for="date" value="Date" />
                            <x-text-input type="date" name="date" id="date" class="mt-2 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="start_time" value="Début (optionnel)" />
                            <x-text-input type="time" name="start_time" id="start_time" class="mt-2 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="end_time" value="Fin (optionnel)" />
                            <x-text-input type="time" name="end_time" id="end_time" class="mt-2 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="reason" value="Motif" />
                            <x-text-input type="text" name="reason" id="reason" class="mt-2 block w-full" placeholder="Travaux, privé..." />
                        </div>
                        <div class="md:col-span-4">
                            <x-primary-button class="px-6 py-3">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Ajouter
                            </x-primary-button>
                        </div>
                    </form>

                    <div class="mt-8">
                        @if($unavailabilities->isEmpty())
                            <div class="text-center py-8 text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm font-medium">Aucune indisponibilité exceptionnelle</p>
                            </div>
                        @else
                            <div class="overflow-hidden rounded-xl border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Plage</th>
                                            <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Motif</th>
                                            <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        @foreach($unavailabilities as $exception)
                                            <tr class="hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $exception->date->format('d/m/Y') }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-600">
                                                    @if($exception->start_time && $exception->end_time)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold">
                                                            {{ $exception->start_time }} - {{ $exception->end_time }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-gray-100 text-gray-600 text-xs font-semibold">
                                                            Journée entière
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600">{{ $exception->reason ?? '—' }}</td>
                                                <td class="px-4 py-3 text-right">
                                                    <form method="POST" action="{{ route('places.unavailability.destroy', [$place->id, $exception->id]) }}" onsubmit="return confirm('Supprimer cette indisponibilité ?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <x-danger-button class="px-3 py-1.5 text-xs">Supprimer</x-danger-button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
        <script>
            const etmlPreset = [
                ['08:00', '08:50'],
                ['09:50', '10:40'],
                ['11:30', '12:20'],
                ['13:10', '14:00'],
                ['15:00', '15:50'],
                ['16:40', '17:25'],
            ];

            const applyEtmlPreset = () => {
                const scheduleType = document.querySelector('[data-weekly-schedule-type]');
                if (scheduleType) {
                    scheduleType.value = 'work_week';
                }

                const visualStart = document.querySelector('[data-visual-start]');
                const visualEnd = document.querySelector('[data-visual-end]');
                if (visualStart) visualStart.value = '08:00';
                if (visualEnd) visualEnd.value = '17:25';

                const groupReserved = document.querySelector('input[name="is_group_reserved"][value="1"]');
                if (groupReserved) {
                    groupReserved.checked = true;
                }

                const groupName = document.getElementById('group_name');
                if (groupName && !groupName.value.trim()) {
                    groupName.value = 'ETML/CFPV';
                }

                [1,2,3,4,5].forEach((day) => {
                    clearDayContainer(day);
                    etmlPreset.forEach(([start, end]) => appendSlot(day, start, end));
                });

                const badge = document.getElementById('school-mode-badge');
                if (badge) {
                    badge.classList.remove('hidden');
                }

                const semesterShortcuts = document.getElementById('school-semester-shortcuts');
                if (semesterShortcuts) {
                    semesterShortcuts.classList.remove('hidden');
                }
            };

            const toggleSchoolBadge = (enabled) => {
                const badge = document.getElementById('school-mode-badge');
                if (!badge) { return; }
                badge.classList.toggle('hidden', !enabled);
            };

            const toggleSemesterShortcuts = (enabled) => {
                const wrapper = document.getElementById('school-semester-shortcuts');
                if (!wrapper) { return; }
                wrapper.classList.toggle('hidden', !enabled);
            };

            const formatDateInput = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };

            const formatDateDisplay = (date) => {
                const day = String(date.getDate()).padStart(2, '0');
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const year = date.getFullYear();
                return `${day}.${month}.${year}`;
            };

            const getBaseAcademicStartYear = () => {
                const today = new Date();
                const month = today.getMonth();
                return month >= 7 ? today.getFullYear() : (today.getFullYear() - 1);
            };

            const getTrimesterRanges = () => {
                const baseYear = getBaseAcademicStartYear();
                return {
                    1: [new Date(baseYear, 7, 25), new Date(baseYear, 9, 31)],
                    2: [new Date(baseYear, 10, 1), new Date(baseYear + 1, 0, 9)],
                    3: [new Date(baseYear + 1, 0, 12), new Date(baseYear + 1, 2, 13)],
                    4: [new Date(baseYear + 1, 2, 16), new Date(baseYear + 1, 4, 29)],
                    5: [new Date(baseYear + 1, 5, 1), new Date(baseYear + 1, 6, 31)],
                };
            };

            const setTrimesterRange = (trimesterNumber) => {
                const startInput = document.getElementById('availability_start_date');
                const endInput = document.getElementById('availability_end_date');
                if (!startInput || !endInput) { return; }

                const ranges = getTrimesterRanges();

                const selected = ranges[Number(trimesterNumber)];
                if (!selected) { return; }

                const [startDate, endDate] = selected;
                startInput.value = formatDateInput(startDate);
                endInput.value = formatDateInput(endDate);
            };

            const refreshTrimesterLabels = () => {
                const ranges = getTrimesterRanges();
                const labels = {
                    1: 'T1',
                    2: 'T2',
                    3: 'T3',
                    4: 'T4',
                    5: 'T5',
                };

                const now = new Date();
                let activeTrimester = null;
                Object.entries(ranges).forEach(([key, selected]) => {
                    const [startDate, endDate] = selected;
                    if (now >= startDate && now <= endDate) {
                        activeTrimester = Number(key);
                    }
                });

                document.querySelectorAll('[data-trimester-label]').forEach((button) => {
                    const key = Number(button.dataset.trimesterShortcut);
                    const selected = ranges[key];
                    if (!selected) { return; }
                    const [startDate, endDate] = selected;
                    button.textContent = `${labels[key]} (${formatDateDisplay(startDate)} → ${formatDateDisplay(endDate)})`;

                    const isActive = activeTrimester === key;
                    button.classList.toggle('ring-2', isActive);
                    button.classList.toggle('ring-indigo-400', isActive);
                    button.classList.toggle('bg-indigo-600', isActive);
                    button.classList.toggle('text-white', isActive);
                    button.classList.toggle('hover:bg-indigo-700', isActive);
                    button.classList.toggle('bg-indigo-50', !isActive && key !== 5);
                    button.classList.toggle('text-indigo-700', !isActive && key !== 5);
                    button.classList.toggle('bg-emerald-50', !isActive && key === 5);
                    button.classList.toggle('text-emerald-700', !isActive && key === 5);
                });

                const schoolYearLabel = document.getElementById('school-year-label');
                if (schoolYearLabel) {
                    const baseYear = getBaseAcademicStartYear();
                    schoolYearLabel.textContent = `Année scolaire active : ${baseYear}/${baseYear + 1}`;
                }
            };

            const createSlotRow = (day, index, start = '', end = '') => {
                const row = document.createElement('div');
                row.className = 'flex flex-col sm:flex-row gap-2 sm:items-center';
                row.setAttribute('data-slot-row', '');
                row.innerHTML = `
                    <input type="time" name="weekly_blocked_slots[${day}][${index}][start]" class="flex-1 border-2 border-gray-200 bg-gray-50 rounded-xl focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all" data-role="start" data-day="${day}" value="${start}">
                    <input type="time" name="weekly_blocked_slots[${day}][${index}][end]" class="flex-1 border-2 border-gray-200 bg-gray-50 rounded-xl focus:border-indigo-500 focus:ring-0 text-sm font-medium text-gray-900 hover:border-gray-300 transition-all" data-role="end" data-day="${day}" value="${end}">
                    <button type="button" class="px-2 py-2 text-xs font-semibold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors self-end sm:self-auto" data-remove-slot>×</button>
                `;
                return row;
            };

            const getDayContainer = (day) => document.querySelector(`[data-day-blocks="${day}"]`);

            const clearDayContainer = (day) => {
                const container = getDayContainer(day);
                if (!container) { return; }
                container.innerHTML = '';
                container.dataset.nextIndex = '0';
            };

            const appendSlot = (day, start = '', end = '') => {
                const container = getDayContainer(day);
                if (!container) { return; }
                const index = Number(container.dataset.nextIndex || '0');
                container.appendChild(createSlotRow(day, index, start, end));
                container.dataset.nextIndex = String(index + 1);
            };

            const hasConfiguredSlots = () => {
                const starts = Array.from(document.querySelectorAll('input[data-role="start"]'));
                const ends = Array.from(document.querySelectorAll('input[data-role="end"]'));
                const hasTimes = starts.some((input) => (input.value || '').trim() !== '')
                    || ends.some((input) => (input.value || '').trim() !== '');

                const availabilityStart = document.getElementById('availability_start_date');
                const availabilityEnd = document.getElementById('availability_end_date');
                const hasDates = Boolean((availabilityStart?.value || '').trim() || (availabilityEnd?.value || '').trim());

                return hasTimes || hasDates;
            };

            const shouldApplyPreset = () => {
                if (!hasConfiguredSlots()) {
                    return true;
                }

                return confirm('Cette action va remplacer les créneaux et/ou dates déjà configurés. Continuer ?');
            };

            document.addEventListener('click', (event) => {
                const addButton = event.target.closest('[data-add-slot]');
                if (addButton) {
                    appendSlot(addButton.dataset.day);
                    return;
                }

                const removeButton = event.target.closest('[data-remove-slot]');
                if (removeButton) {
                    const row = removeButton.closest('[data-slot-row]');
                    const container = row?.closest('[data-day-blocks]');
                    row?.remove();
                    if (container && container.querySelectorAll('[data-slot-row]').length === 0) {
                        appendSlot(container.dataset.dayBlocks || container.getAttribute('data-day-blocks'));
                    }
                    return;
                }

                const etmlButton = event.target.closest('[data-apply-etml]');
                if (etmlButton) {
                    if (!shouldApplyPreset()) {
                        return;
                    }
                    applyEtmlPreset();
                    return;
                }

                const trimesterButton = event.target.closest('[data-trimester-shortcut]');
                if (trimesterButton) {
                    setTrimesterRange(trimesterButton.dataset.trimesterShortcut);
                    return;
                }

                const button = event.target.closest('[data-quick-slot], [data-clear-slot]');
                if (!button) { return; }
                const day = button.dataset.day;
                const start = button.dataset.start;
                const end = button.dataset.end;
                if (button.hasAttribute('data-clear-slot')) {
                    if (day === 'all') {
                        [0,1,2,3,4,5,6].forEach((d) => {
                            clearDayContainer(d);
                            appendSlot(d);
                        });
                        return;
                    }
                    clearDayContainer(day);
                    appendSlot(day);
                    return;
                }
                if (day === 'all') {
                    [0,1,2,3,4,5,6].forEach((d) => {
                        clearDayContainer(d);
                        appendSlot(d, start, end);
                    });
                    return;
                }
                clearDayContainer(day);
                appendSlot(day, start, end);
            });

            document.addEventListener('change', (event) => {
                const schoolToggle = event.target.closest('[data-school-group-toggle]');
                if (!schoolToggle) { return; }
                if (schoolToggle.checked) {
                    toggleSchoolBadge(true);
                    toggleSemesterShortcuts(true);
                } else {
                    toggleSchoolBadge(false);
                    toggleSemesterShortcuts(false);
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                refreshTrimesterLabels();

                const schoolToggle = document.querySelector('[data-school-group-toggle]');
                const groupName = document.getElementById('group_name');
                const scheduleType = document.querySelector('[data-weekly-schedule-type]');

                const looksLikeSchoolPreset = groupName && scheduleType
                    ? (groupName.value.trim().toUpperCase() === 'ETML/CFPV' && scheduleType.value === 'work_week')
                    : false;

                if (schoolToggle && looksLikeSchoolPreset) {
                    schoolToggle.checked = true;
                    toggleSchoolBadge(true);
                    toggleSemesterShortcuts(true);
                }
            });
        </script>
    @endpush
</x-app-layout>
