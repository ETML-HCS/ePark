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
    @pointerup.window="isDragging = false"
    @pointercancel.window="isDragging = false"
>
    <input type="hidden" name="rental_rules_present" value="1">
    <input type="hidden" name="pricing_mode" :value="pricingMode">

    {{-- Sélecteur simple en 2 modes --}}
    <div class="space-y-2">
        <span class="block text-sm font-semibold text-[#18263a]">Tarif &amp; horaires <span class="text-rose-700">*</span></span>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <button
                type="button"
                @click="setPricingMode('single')"
                :aria-pressed="pricingMode === 'single'"
                :class="pricingMode === 'single' ? 'border-[#4338ca] bg-[#eef2ff] text-[#312e81] ring-1 ring-[#4338ca]' : 'border-[#dce3ed] bg-white hover:bg-slate-50'"
                class="p-3.5 rounded-xl border text-left transition-colors">
                <strong class="block text-sm">Tarif unique (7j/7)</strong>
                <span class="block text-xs text-slate-600 mt-0.5">Un même prix à tout moment.</span>
            </button>
            <button
                type="button"
                @click="setPricingMode('rules')"
                :aria-pressed="pricingMode === 'rules'"
                :class="pricingMode === 'rules' ? 'border-[#4338ca] bg-[#eef2ff] text-[#312e81] ring-1 ring-[#4338ca]' : 'border-[#dce3ed] bg-white hover:bg-slate-50'"
                class="p-3.5 rounded-xl border text-left transition-colors">
                <strong class="block text-sm">Selon les jours / créneaux</strong>
                <span class="block text-xs text-slate-600 mt-0.5">Peignez directement vos tarifs sur la semaine.</span>
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
                @foreach(['0.00' => 'Gratuit', '1.00' => '1.00', '2.00' => '2.00', '2.50' => '2.50', '3.00' => '3.00'] as $val => $label)
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

    {{-- Mode 2 : Éditeur visuel direct « Pinceau tarifaire » --}}
    <div x-show="pricingMode === 'rules'" x-cloak class="rounded-xl border border-[#dce3ed] bg-[#f8fafc] p-4 space-y-4">
        {{-- Étape 1 : Choisir un pinceau tarifaire --}}
        <div class="space-y-2.5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-xs font-bold text-[#18263a]">1. Choisissez un tarif, puis cliquez sur le planning</span>
                <span class="status-pill is-neutral" x-text="compiledRules.length + ' / ' + maxRules + ' plages'"></span>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <template x-for="brush in brushes" :key="brush.id">
                    <button
                        type="button"
                        @click="activeBrushId = brush.id"
                        :aria-pressed="activeBrushId === brush.id"
                        :class="activeBrushId === brush.id ? brushPalette(brush).pill + ' ring-2 ring-offset-1 ring-[#4338ca] font-bold shadow-sm' : 'border-[#dce3ed] bg-white text-slate-700 hover:bg-slate-50 font-semibold'"
                        class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-xs transition-all">
                        <span class="w-3 h-3 rounded-full shrink-0" :class="brushPalette(brush).dot"></span>
                        <span x-text="formatFullCents(brush.cents)"></span>
                    </button>
                </template>

                <button
                    type="button"
                    x-show="brushes.length < maxRules"
                    @click="addBrush()"
                    class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-dashed border-[#4338ca] bg-white text-xs font-semibold text-[#4338ca] hover:bg-[#eef2ff] transition-colors">
                    <span aria-hidden="true">+</span> Tarif
                </button>

                <button
                    type="button"
                    @click="activeBrushId = 'eraser'"
                    :aria-pressed="activeBrushId === 'eraser'"
                    :class="activeBrushId === 'eraser' ? 'border-slate-700 bg-slate-800 text-white font-bold shadow-sm' : 'border-[#dce3ed] bg-white text-slate-600 hover:bg-slate-100 font-semibold'"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border text-xs transition-all ml-auto">
                    <span class="w-3 h-3 rounded-sm border border-current opacity-70 inline-block"></span>
                    Fermé
                </button>
            </div>

            {{-- Réglage rapide du tarif du pinceau sélectionné --}}
            <div x-show="activeBrush !== null" class="flex flex-wrap items-center gap-2 p-2.5 rounded-lg bg-white border border-[#dce3ed]">
                <label :for="'active-brush-price-' + (activeBrush?.id || '')" class="text-xs font-semibold text-slate-600">Tarif sélectionné :</label>
                <div class="relative w-32">
                    <input
                        :id="'active-brush-price-' + (activeBrush?.id || '')"
                        type="number"
                        min="0"
                        max="500"
                        step="0.05"
                        :value="activeBrush?.priceInput ?? ''"
                        @input="updateActiveBrushPrice($event.target.value)"
                        class="ep-input !min-h-[34px] !py-1 pr-11 text-xs font-bold">
                    <span class="absolute right-2 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500">CHF/h</span>
                </div>
                <div class="flex flex-wrap gap-1">
                    @foreach(['0.00' => 'Gratuit', '1.00' => '1.-', '2.00' => '2.-', '2.50' => '2.50', '3.00' => '3.-'] as $val => $label)
                        <button
                            type="button"
                            @click="setActiveBrushQuickPrice('{{ $val }}')"
                            :class="activeBrush && activeBrush.cents === {{ (int) round(((float) $val) * 100) }} ? 'bg-[#4338ca] text-white border-[#4338ca]' : 'bg-[#f8fafc] text-slate-700 border-[#dce3ed] hover:bg-[#eef2ff]'"
                            class="px-2 py-1 rounded border text-[11px] font-semibold">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <button
                    type="button"
                    x-show="brushes.length > 1"
                    @click="removeBrush(activeBrush.id)"
                    class="ml-auto text-[11px] font-semibold text-rose-700 hover:underline">
                    Supprimer ce tarif
                </button>
            </div>
        </div>

        {{-- Étape 2 : Grille interactive 7 jours × 4 créneaux --}}
        <div class="rounded-xl border border-[#dce3ed] bg-white p-3.5 space-y-2.5">
            <div class="grid grid-cols-[58px_repeat(7,minmax(0,1fr))] sm:grid-cols-[68px_repeat(7,minmax(0,1fr))] gap-1.5 items-center select-none">
                <div></div>
                <template x-for="day in dayOptions" :key="'head-' + day.id">
                    <button
                        type="button"
                        @click="paintDay(day.id)"
                        title="Cliquer pour appliquer le tarif à toute la journée"
                        :class="(day.id === 6 || day.id === 0) ? 'text-[#4338ca] hover:bg-[#eef2ff]' : 'text-slate-700 hover:bg-slate-100'"
                        class="py-1 rounded text-center text-[11px] font-bold transition-colors"
                        x-text="day.short">
                    </button>
                </template>

                <template x-for="band in bands" :key="'row-' + band.key">
                    <div class="contents">
                        <button
                            type="button"
                            @click="paintBand(band.key)"
                            title="Cliquer pour appliquer le tarif sur toute la ligne"
                            class="text-right pr-1.5 py-1 rounded hover:bg-slate-100 leading-tight transition-colors">
                            <span class="block text-[10px] sm:text-[11px] font-semibold text-slate-700" x-text="band.label"></span>
                            <span class="block text-[9px] text-slate-500" x-text="band.hours"></span>
                        </button>
                        <template x-for="day in dayOptions" :key="band.key + '-' + day.id">
                            <button
                                type="button"
                                @pointerdown.prevent="startPaint(day.id, band.key)"
                                @pointerenter="continuePaint(day.id, band.key)"
                                @keydown.enter.prevent="startPaint(day.id, band.key); isDragging = false"
                                @keydown.space.prevent="startPaint(day.id, band.key); isDragging = false"
                                class="h-9 rounded-lg flex items-center justify-center text-[10px] sm:text-xs font-bold tabular-nums leading-none overflow-hidden transition-colors cursor-pointer"
                                :class="cellInfo(day.id, band).cellClass"
                                :title="cellInfo(day.id, band).title"
                                x-text="cellInfo(day.id, band).text">
                            </button>
                        </template>
                    </div>
                </template>
            </div>

            <p class="text-[11px] text-slate-500 text-center">
                Astuce : cliquez ou glissez sur les cases, ou cliquez sur un jour (<span class="font-semibold">Lun…Dim</span>) / créneau (<span class="font-semibold">Matin…Nuit</span>).
            </p>
        </div>

        {{-- Alerte si > 5 plages distinctes --}}
        <div x-show="limitWarning" x-cloak class="p-2.5 rounded-lg bg-amber-50 border border-amber-200 text-xs text-amber-900" role="alert" x-text="limitWarning"></div>

        {{-- Raccourcis rapides 1 clic --}}
        <div class="space-y-1.5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="text-[11px] font-semibold text-slate-600">Raccourcis rapides :</span>
                <button
                    type="button"
                    @click="clearGrid()"
                    class="text-[11px] font-semibold text-slate-600 hover:text-rose-700 hover:underline">
                    Tout vider
                </button>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <button
                    type="button"
                    @click="applyStarterSemaineWeekend()"
                    class="px-2.5 py-1.5 rounded-lg border border-[#4338ca] bg-[#eef2ff] text-xs font-bold text-[#312e81] hover:bg-indigo-100 transition-colors">
                    Semaine gratuit + Week-end payant
                </button>
                <template x-for="zone in quickZones" :key="zone.key">
                    <button
                        type="button"
                        @click="applyQuickZone(zone)"
                        class="px-2.5 py-1.5 rounded-lg border border-[#dce3ed] bg-white text-xs font-semibold text-slate-700 hover:bg-[#eef2ff] hover:border-[#4338ca] transition-colors"
                        x-text="zone.label">
                    </button>
                </template>
            </div>
        </div>

        {{-- Résumé généré automatiquement + champs cachés soumis au serveur --}}
        <div x-show="compiledRules.length > 0" class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-[#dce3ed]">
            <template x-for="(rule, index) in compiledRules" :key="rule.key">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-white border border-[#dce3ed] text-xs">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="rule.dotClass"></span>
                    <strong class="text-[#18263a]" x-text="rule.daysLabel"></strong>
                    <span class="text-slate-500" x-text="'· ' + rule.segmentsLabel"></span>
                    <strong :class="rule.isFree ? 'text-[#166534]' : 'text-[#4338ca]'" x-text="rule.priceLabel"></strong>

                    <input type="hidden" :name="'rental_rules[' + index + '][label]'" :value="rule.label">
                    <input type="hidden" :name="'rental_rules[' + index + '][hourly_price]'" :value="rule.hourly_price">
                    <template x-for="dayId in rule.days" :key="'d-' + index + '-' + dayId">
                        <input type="hidden" :name="'rental_rules[' + index + '][days][]'" :value="dayId">
                    </template>
                    <template x-for="segKey in rule.segments" :key="'s-' + index + '-' + segKey">
                        <input type="hidden" :name="'rental_rules[' + index + '][segments][]'" :value="segKey">
                    </template>
                </div>
            </template>
        </div>

        <p x-show="compiledRules.length === 0" class="text-xs text-rose-700 font-semibold">
            Peignez au moins un créneau sur le planning ou repassez en « Tarif unique ».
        </p>
    </div>
</div>
