@php
    $oldRules = old('rental_rules');
    if (is_array($oldRules)) {
        $initialRentalRules = array_values($oldRules);
    } elseif (isset($place) && $place instanceof \App\Models\Place) {
        $initialRentalRules = $place->normalizedRentalRules();
    } else {
        $initialRentalRules = [];
    }
    $fallbackPrice = old('hourly_price', isset($place) ? number_format(($place->hourly_price_cents ?? 0) / 100, 2, '.', '') : '2.00');
@endphp

<div
    class="pt-5 border-t border-[#dce3ed] space-y-4"
    x-data="rentalRulesEditor(@js([
        'initialRules' => $initialRentalRules,
        'defaultPrice' => $fallbackPrice,
        'maxRules' => \App\Models\Place::MAX_RENTAL_RULES,
    ]))"
>
    <input type="hidden" name="rental_rules_present" value="1">

    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h2 class="text-sm font-bold text-[#18263a]">
                Plages de location &amp; tarifs par jour
                <span class="text-xs font-normal text-slate-500">(optionnel, max {{ \App\Models\Place::MAX_RENTAL_RULES }})</span>
            </h2>
            <p class="text-xs text-slate-600 mt-0.5">
                Définissez jusqu’à {{ \App\Models\Place::MAX_RENTAL_RULES }} plages (ex. : semaine en journée gratuit, week-end payant). Sans plage, la place reste ouverte au tarif unique ci-dessus.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="status-pill is-neutral" x-text="rules.length + ' / ' + maxRules + ' plages'"></span>
            <button
                type="button"
                x-show="rules.length > 0"
                x-cloak
                @click="clearAll()"
                class="text-xs font-semibold text-rose-700 hover:underline">
                Tout effacer
            </button>
        </div>
    </div>

    {{-- Boutons raccourcis rapides --}}
    <div class="p-3.5 rounded-xl bg-[#f3f6fa] border border-[#dce3ed] space-y-2.5">
        <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-semibold text-slate-700">Ajouter rapidement une plage :</span>
            <span x-show="remainingSlots === 0" x-cloak class="text-xs font-semibold text-amber-800">Maximum de {{ \App\Models\Place::MAX_RENTAL_RULES }} plages atteint</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <template x-for="preset in presets" :key="preset.key">
                <button
                    type="button"
                    @click="addPreset(preset)"
                    :disabled="remainingSlots === 0"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#dce3ed] bg-white text-xs font-semibold text-[#18263a] hover:bg-[#eef2ff] hover:border-[#4338ca] transition-colors disabled:opacity-40">
                    <span aria-hidden="true" class="text-[#4338ca] font-bold">+</span>
                    <span x-text="preset.label"></span>
                    <span class="text-[11px] font-normal text-slate-500" x-text="'· ' + preset.badge"></span>
                </button>
            </template>
        </div>
    </div>

    {{-- Aperçu graphique temps réel des prix par jour et créneau --}}
    <div x-show="rules.length > 0" x-cloak class="rounded-xl border border-[#dce3ed] bg-[#f8fafc] p-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-xs font-bold text-[#18263a] uppercase tracking-wide">Aperçu visuel de la semaine &amp; tarifs</h3>
                <p class="text-[11px] text-slate-600">Mis à jour en direct selon vos plages (prix horaire en CHF).</p>
            </div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-600">
                <span x-show="hasFreeRule" class="inline-flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span>
                    Gratuit
                </span>
                <template x-for="cents in sortedPaidRates" :key="'legend-' + cents">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-2.5 h-2.5 rounded-sm inline-block" :class="paletteForCents(cents).dot"></span>
                        <span x-text="formatFullCents(cents)"></span>
                    </span>
                </template>
                <span class="inline-flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-sm bg-slate-200 inline-block"></span>
                    Fermé
                </span>
            </div>
        </div>

        <div class="grid grid-cols-[64px_repeat(7,minmax(0,1fr))] gap-1.5 items-center">
            <div></div>
            <template x-for="day in dayOptions" :key="'head-' + day.id">
                <span class="text-center text-[11px] font-bold text-slate-700" x-text="day.short"></span>
            </template>

            <template x-for="band in visualBands" :key="'row-' + band.key">
                <div class="contents">
                    <div class="text-right pr-1.5 leading-tight">
                        <span class="block text-[11px] font-semibold text-slate-700" x-text="band.label"></span>
                        <span class="block text-[9px] text-slate-500" x-text="band.hours"></span>
                    </div>
                    <template x-for="day in dayOptions" :key="band.key + '-' + day.id">
                        <div
                            class="h-8 rounded-md flex items-center justify-center text-[11px] font-bold leading-none select-none cursor-help transition-colors"
                            :class="cellFor(day.id, band).cellClass"
                            :title="cellFor(day.id, band).title"
                            x-text="cellFor(day.id, band).text">
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Liste des plages configurées --}}
    <div x-show="rules.length > 0" x-cloak class="space-y-4">
        <template x-for="(rule, index) in rules" :key="rule.uid">
            <article class="rounded-xl border border-[#dce3ed] bg-white p-4 space-y-4 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-[#eef2f7]">
                    <div class="flex items-center gap-2 flex-1 min-w-[200px]">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-md bg-[#eef2ff] text-[#4338ca] text-xs font-bold shrink-0" x-text="index + 1"></span>
                        <input
                            type="text"
                            :name="'rental_rules[' + index + '][label]'"
                            x-model="rule.label"
                            maxlength="60"
                            placeholder="Nom de la plage (ex. : Semaine journée)"
                            class="ep-input !min-h-[38px] !py-1.5 text-sm font-semibold">
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span
                            class="status-pill"
                            :class="isFree(rule) ? 'is-confirmed' : 'is-neutral'"
                            x-text="isFree(rule) ? 'Gratuit' : (Number(rule.hourly_price || 0).toFixed(2) + ' CHF/h')">
                        </span>
                        <button
                            type="button"
                            @click="removeRule(index)"
                            class="px-2.5 py-1.5 rounded-lg border border-rose-200 text-xs font-semibold text-rose-700 hover:bg-rose-50"
                            :aria-label="'Supprimer la plage ' + (index + 1)">
                            Supprimer
                        </button>
                    </div>
                </div>

                {{-- Jours de la semaine --}}
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-slate-700">Jours concernés <span class="text-rose-700">*</span></span>
                        <div class="flex flex-wrap gap-1.5">
                            <button type="button" @click="setDayGroup(rule, 'weekdays')" class="px-2 py-0.5 rounded text-[11px] font-semibold bg-[#f3f6fa] text-slate-700 hover:bg-[#eef2ff] hover:text-[#4338ca]">Lun–Ven</button>
                            <button type="button" @click="setDayGroup(rule, 'weekend')" class="px-2 py-0.5 rounded text-[11px] font-semibold bg-[#f3f6fa] text-slate-700 hover:bg-[#eef2ff] hover:text-[#4338ca]">Sam–Dim</button>
                            <button type="button" @click="setDayGroup(rule, 'all')" class="px-2 py-0.5 rounded text-[11px] font-semibold bg-[#f3f6fa] text-slate-700 hover:bg-[#eef2ff] hover:text-[#4338ca]">7j/7</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-1.5">
                        <template x-for="day in dayOptions" :key="day.id">
                            <button
                                type="button"
                                @click="toggleDay(rule, day.id)"
                                :aria-pressed="rule.days.includes(day.id)"
                                :class="rule.days.includes(day.id) ? 'bg-[#4338ca] text-white border-[#4338ca]' : 'bg-white text-slate-700 border-[#dce3ed] hover:bg-slate-50'"
                                class="py-2 rounded-lg border text-xs font-semibold transition-colors"
                                x-text="day.short">
                            </button>
                        </template>
                    </div>
                    <template x-for="dayId in rule.days" :key="'day-' + dayId">
                        <input type="hidden" :name="'rental_rules[' + index + '][days][]'" :value="dayId">
                    </template>
                    <p x-show="rule.days.length === 0" class="text-xs text-rose-700">Sélectionnez au moins un jour.</p>
                </div>

                {{-- Créneaux horaires --}}
                <div class="space-y-2">
                    <span class="block text-xs font-semibold text-slate-700">Créneaux ouverts <span class="text-rose-700">*</span></span>
                    <div class="grid grid-cols-2 sm:grid-cols-5 gap-1.5">
                        <template x-for="seg in segmentOptions" :key="seg.key">
                            <button
                                type="button"
                                @click="toggleSegment(rule, seg.key)"
                                :aria-pressed="rule.segments.includes(seg.key)"
                                :class="rule.segments.includes(seg.key) ? 'bg-[#eef2ff] text-[#312e81] border-[#4338ca] ring-1 ring-[#4338ca]' : 'bg-white text-slate-700 border-[#dce3ed] hover:bg-slate-50'"
                                class="px-2 py-2 rounded-lg border text-left transition-colors">
                                <strong class="block text-xs" x-text="seg.label"></strong>
                                <span class="block text-[11px] text-slate-500" x-text="seg.hours"></span>
                            </button>
                        </template>
                    </div>
                    <template x-for="segKey in rule.segments" :key="'seg-' + segKey">
                        <input type="hidden" :name="'rental_rules[' + index + '][segments][]'" :value="segKey">
                    </template>
                    <p x-show="rule.segments.length === 0" class="text-xs text-rose-700">Sélectionnez au moins un créneau.</p>
                </div>

                {{-- Tarif de la plage --}}
                <div class="space-y-1.5">
                    <label :for="'rental-price-' + rule.uid" class="block text-xs font-semibold text-slate-700">Tarif de cette plage (CHF/h)</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative w-36">
                            <input
                                :id="'rental-price-' + rule.uid"
                                type="number"
                                :name="'rental_rules[' + index + '][hourly_price]'"
                                x-model="rule.hourly_price"
                                min="0"
                                max="500"
                                step="0.05"
                                required
                                class="ep-input !min-h-[38px] !py-1.5 pr-12 text-sm font-bold">
                            <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] font-bold text-slate-500">CHF/h</span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(['0.00' => 'Gratuit', '1.00' => '1.00', '2.00' => '2.00', '2.50' => '2.50', '3.00' => '3.00'] as $val => $label)
                                <button
                                    type="button"
                                    @click="setRulePrice(rule, '{{ $val }}')"
                                    :class="Number(rule.hourly_price) === {{ (float) $val }} ? 'bg-[#4338ca] text-white border-[#4338ca]' : 'bg-white text-slate-700 border-[#dce3ed]'"
                                    class="px-2.5 py-1.5 rounded-lg border text-xs font-semibold">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>
        </template>

        <div x-show="hasOverlap" x-cloak class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900">
            Certaines plages couvrent le même jour et le même créneau : la première plage de la liste sera appliquée en priorité.
        </div>

        <p class="text-xs text-slate-600">
            Mode plages actif : seuls les jours et créneaux configurés ci-dessus seront ouverts à la location, au tarif de leur plage.
        </p>
    </div>
</div>
