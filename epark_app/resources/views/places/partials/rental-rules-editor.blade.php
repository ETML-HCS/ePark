@php
    $oldRules = old('rental_rules');
    if (old('rental_rules_present') !== null) {
        $initialRentalRules = is_array($oldRules) ? array_values($oldRules) : [];
    } elseif (isset($place) && $place instanceof \App\Models\Place) {
        $initialRentalRules = $place->normalizedRentalRules();
    } else {
        $initialRentalRules = [];
    }

    $initialPricingMode = old('pricing_mode');
    if (! in_array($initialPricingMode, ['single', 'rules'], true)) {
        $initialPricingMode = $initialRentalRules !== [] ? 'rules' : 'single';
    }

    $fallbackPrice = old('hourly_price', isset($place) ? number_format(($place->hourly_price_cents ?? 0) / 100, 2, '.', '') : '2.00');
@endphp

<div
    class="pt-4 border-t border-[#dce3ed] space-y-4"
    x-data="rentalRulesEditor(@js([
        'initialRules' => $initialRentalRules,
        'initialMode' => $initialPricingMode,
        'defaultPrice' => $fallbackPrice,
        'maxRules' => \App\Models\Place::MAX_RENTAL_RULES,
    ]))"
>
    <input type="hidden" name="rental_rules_present" value="1">
    <input type="hidden" name="pricing_mode" :value="pricingMode">

    {{-- Sélecteur simple en 2 modes --}}
    <div class="space-y-2">
        <span class="block text-sm font-semibold text-[#18263a]">Tarification &amp; plages de location <span class="text-rose-700">*</span></span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <button
                type="button"
                @click="setPricingMode('single')"
                :aria-pressed="pricingMode === 'single'"
                :class="pricingMode === 'single' ? 'border-[#4338ca] bg-[#eef2ff] text-[#312e81] ring-1 ring-[#4338ca]' : 'border-[#dce3ed] bg-white hover:bg-slate-50'"
                class="p-3.5 rounded-xl border text-left transition-colors">
                <strong class="block text-sm">Tarif unique (7j/7)</strong>
                <span class="block text-xs text-slate-600 mt-0.5">Un même prix horaire à tout moment.</span>
            </button>
            <button
                type="button"
                @click="setPricingMode('rules')"
                :aria-pressed="pricingMode === 'rules'"
                :class="pricingMode === 'rules' ? 'border-[#4338ca] bg-[#eef2ff] text-[#312e81] ring-1 ring-[#4338ca]' : 'border-[#dce3ed] bg-white hover:bg-slate-50'"
                class="p-3.5 rounded-xl border text-left transition-colors">
                <strong class="block text-sm">Selon les jours / créneaux</strong>
                <span class="block text-xs text-slate-600 mt-0.5">Ex. : semaine gratuit, week-end payant (max {{ \App\Models\Place::MAX_RENTAL_RULES }}).</span>
            </button>
        </div>
    </div>

    {{-- Mode 1 : Tarif unique --}}
    <div x-show="pricingMode === 'single'" class="p-4 rounded-xl bg-[#f3f6fa] border border-[#dce3ed] space-y-3">
        <label for="hourly_price" class="block text-sm font-semibold">Tarif horaire (CHF)</label>
        <div class="grid gap-3 sm:grid-cols-2 items-start">
            <div class="relative">
                <input
                    type="number"
                    id="hourly_price"
                    name="hourly_price"
                    x-model="defaultPrice"
                    min="0"
                    step="0.05"
                    class="ep-input pr-14 font-bold text-lg"
                    :disabled="pricingMode !== 'single'"
                    :required="pricingMode === 'single'">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-500">CHF/h</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
                @foreach(['0.00' => 'Gratuit', '1.00' => '1.00', '2.00' => '2.00', '2.50' => '2.50', '3.00' => '3.00', '3.50' => '3.50'] as $val => $label)
                    <button
                        type="button"
                        @click="defaultPrice = '{{ $val }}'"
                        :class="defaultPrice !== '' && Number(defaultPrice) === {{ (float) $val }} ? 'bg-[#4338ca] text-white border-[#4338ca]' : 'bg-white text-slate-700 border-[#dce3ed]'"
                        class="px-3 py-2 rounded-lg border text-xs font-semibold">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Mode 2 : Plages par jour et créneau (max 5) --}}
    <div x-show="pricingMode === 'rules'" x-cloak class="space-y-4">
        {{-- Aperçu graphique instantané : lecture < 3 secondes --}}
        <div x-show="rules.length > 0" class="rounded-xl border border-[#dce3ed] bg-white p-4 space-y-3 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="text-xs font-bold text-[#18263a] uppercase tracking-wide">Votre planning &amp; tarifs en un coup d’œil</h3>
                <span class="status-pill is-neutral" x-text="rules.length + ' / ' + maxRules + ' plages'"></span>
            </div>

            {{-- Résumé 1 ligne par plage --}}
            <div class="flex flex-wrap gap-2">
                <template x-for="item in liveSummaries" :key="'sum-' + item.uid">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-[#f3f6fa] border border-[#dce3ed] text-xs">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="item.dotClass"></span>
                        <strong class="text-[#18263a]" x-text="item.days"></strong>
                        <span class="text-slate-500" x-text="'· ' + item.segments"></span>
                        <strong :class="item.isFree ? 'text-[#166534]' : 'text-[#4338ca]'" x-text="item.price"></strong>
                    </span>
                </template>
            </div>

            {{-- Matrice épurée : seules les plages ouvertes sont colorées --}}
            <div class="grid grid-cols-[54px_repeat(7,minmax(0,1fr))] sm:grid-cols-[62px_repeat(7,minmax(0,1fr))] gap-1 sm:gap-1.5 items-center pt-1">
                <div></div>
                <template x-for="day in dayOptions" :key="'head-' + day.id">
                    <span
                        class="text-center text-[10px] sm:text-[11px] font-bold"
                        :class="(day.id === 6 || day.id === 0) ? 'text-[#4338ca]' : 'text-slate-700'"
                        x-text="day.short">
                    </span>
                </template>

                <template x-for="band in visualBands" :key="'row-' + band.key">
                    <div class="contents">
                        <div class="text-right pr-1 sm:pr-1.5 leading-tight">
                            <span class="block text-[10px] sm:text-[11px] font-semibold text-slate-700" x-text="band.label"></span>
                            <span class="block text-[9px] text-slate-500" x-text="band.hours"></span>
                        </div>
                        <template x-for="day in dayOptions" :key="band.key + '-' + day.id">
                            <div
                                class="h-7 rounded-md flex items-center justify-center text-[9px] sm:text-[11px] font-bold tabular-nums leading-none overflow-hidden select-none cursor-help transition-colors"
                                :class="cellFor(day.id, band).cellClass"
                                :title="cellFor(day.id, band).title"
                                x-text="cellFor(day.id, band).text">
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>

        {{-- Modèles (remplacent) + Boutons d'ajout (ajoutent) clairement séparés --}}
        <div class="p-3.5 rounded-xl bg-[#f3f6fa] border border-[#dce3ed] space-y-3">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-slate-700">Modèles prêts à l’emploi <span class="font-normal text-slate-500">(remplacent la liste)</span> :</span>
                    <button
                        type="button"
                        x-show="rules.length > 0"
                        @click="clearAll()"
                        class="text-xs font-semibold text-rose-700 hover:underline">
                        Revenir au tarif unique
                    </button>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="applyComboSemaineWeekend()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#4338ca] bg-[#eef2ff] text-xs font-bold text-[#312e81] hover:bg-indigo-100 transition-colors">
                        <span>Semaine gratuit + Week-end payant</span>
                    </button>
                    <button
                        type="button"
                        @click="applyComboSemaineTravail()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-[#dce3ed] bg-white text-xs font-semibold text-[#18263a] hover:bg-[#eef2ff] hover:border-[#4338ca] transition-colors">
                        <span>Semaine de travail uniquement</span>
                    </button>
                </div>
            </div>

            <div class="pt-2.5 border-t border-[#dce3ed] space-y-1.5">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span class="text-xs font-semibold text-slate-700">Ajouter une plage <span class="font-normal text-slate-500" x-text="'(' + rules.length + ' / ' + maxRules + ')'"></span> :</span>
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
        </div>

        {{-- État vide si l'utilisateur supprime toutes les plages en mode plages --}}
        <div x-show="rules.length === 0" class="rounded-xl border border-dashed border-[#cbd5e1] bg-[#f8fafc] p-5 text-center space-y-2">
            <p class="text-sm font-semibold text-[#18263a]">Aucune plage configurée</p>
            <p class="text-xs text-slate-600">Choisissez un modèle ou un raccourci ci-dessus pour ajouter une plage, ou revenez au tarif unique.</p>
            <button type="button" @click="setPricingMode('single')" class="booking-secondary !min-h-[36px] !py-1.5 !px-3 !text-xs mt-1">
                Passer au tarif unique
            </button>
        </div>

        {{-- Liste des plages configurées --}}
        <div x-show="rules.length > 0" class="space-y-3">
            <template x-for="(rule, index) in rules" :key="rule.uid">
                <article class="rounded-xl border border-[#dce3ed] bg-white p-4 space-y-3.5 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-[#eef2f7]">
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
                                x-text="isFree(rule) ? 'Gratuit' : (rule.hourly_price === '' ? '—' : Number(rule.hourly_price).toFixed(2) + ' CHF/h')">
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
                                        :class="rule.hourly_price !== '' && Number(rule.hourly_price) === {{ (float) $val }} ? 'bg-[#4338ca] text-white border-[#4338ca]' : 'bg-white text-slate-700 border-[#dce3ed]'"
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
                Certaines plages se chevauchent sur un même jour et créneau : la première plage de la liste est prioritaire.
            </div>
        </div>
    </div>
</div>
