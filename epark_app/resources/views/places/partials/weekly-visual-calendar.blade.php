@php
    $heightClass = $heightClass ?? 'h-20';
    $labelClass = $labelClass ?? 'text-slate-600';
    $compact = (bool) ($compact ?? false);
    $dayLabels = [1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V', 6 => 'S', 0 => 'D'];
    $dayFullNames = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 0 => 'Dimanche'];

    $toMinutes = static function (?string $time, int $fallback): int {
        if (! $time || ! str_contains($time, ':')) {
            return $fallback;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return max(0, min(1439, ($hours * 60) + $minutes));
    };

    $overlaps = static function (int $aStart, int $aEnd, int $bStart, int $bEnd): bool {
        return $aStart < $bEnd && $aEnd > $bStart;
    };

    $formatTime = static function (int $minutes): string {
        $hours = (int) floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    };

    $formatCompactPrice = static function (int $cents): string {
        if ($cents === 0) {
            return 'Grat.';
        }
        $chf = $cents / 100;
        if (abs($chf - round($chf)) < 0.001) {
            return (int) round($chf).'.-';
        }

        return number_format($chf, 2, '.', '');
    };

    $hasRentalRules = $place->hasRentalRules();
    $rentalRules = $hasRentalRules ? $place->normalizedRentalRules() : [];
    $blockedRelation = $place->relationLoaded('blockedSlots') ? $place->blockedSlots : collect();
@endphp

@if($hasRentalRules)
    @php
        $bands = [
            ['key' => 'matin', 'label' => 'Mat.', 'full' => 'Matin (08:00–12:00)', 'segments' => ['matin_travail', 'journee'], 'start' => 480, 'end' => 720],
            ['key' => 'aprem', 'label' => 'Ap.-m.', 'full' => 'Après-midi (12:00–17:30)', 'segments' => ['aprem_travail', 'journee'], 'start' => 720, 'end' => 1050],
            ['key' => 'soir', 'label' => 'Soir', 'full' => 'Soir (18:00–00:00)', 'segments' => ['soir'], 'start' => 1080, 'end' => 1440],
            ['key' => 'nuit', 'label' => 'Nuit', 'full' => 'Nuit (00:00–07:30)', 'segments' => ['nuit'], 'start' => 0, 'end' => 450],
        ];

        $paidRates = collect($rentalRules)
            ->pluck('hourly_price_cents')
            ->filter(fn (int $cents) => $cents > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $paidPalettes = [
            ['cell' => 'bg-[#4338ca] text-white', 'dot' => 'bg-[#4338ca]'],
            ['cell' => 'bg-violet-600 text-white', 'dot' => 'bg-violet-600'],
            ['cell' => 'bg-amber-600 text-white', 'dot' => 'bg-amber-600'],
            ['cell' => 'bg-sky-700 text-white', 'dot' => 'bg-sky-700'],
            ['cell' => 'bg-rose-600 text-white', 'dot' => 'bg-rose-600'],
        ];

        $ratePaletteMap = [];
        foreach ($paidRates as $idx => $cents) {
            $ratePaletteMap[$cents] = $paidPalettes[$idx % count($paidPalettes)];
        }

        $hasFreeBand = collect($rentalRules)->contains(fn (array $r) => $r['hourly_price_cents'] === 0);
        $hasAnyBlocked = false;
    @endphp

    <div class="w-full {{ $compact ? '' : 'max-w-md' }}" aria-label="Grille hebdomadaire des disponibilités et tarifs">
        <div class="grid grid-cols-[40px_repeat(7,minmax(0,1fr))] gap-1 items-center">
            <div></div>
            @foreach($dayLabels as $dayIndex => $dayLabel)
                <span class="text-center text-[10px] font-bold {{ $labelClass }}">{{ $dayLabel }}</span>
            @endforeach

            @foreach($bands as $band)
                <span class="text-[10px] font-semibold text-slate-500 text-right pr-1 leading-tight">{{ $band['label'] }}</span>
                @foreach($dayLabels as $dayIndex => $dayLabel)
                    @php
                        $matchedRule = null;
                        foreach ($rentalRules as $rule) {
                            if (! in_array($dayIndex, $rule['days'], true)) {
                                continue;
                            }
                            foreach ($band['segments'] as $candidateSeg) {
                                if (in_array($candidateSeg, $rule['segments'], true)) {
                                    $matchedRule = $rule;
                                    break 2;
                                }
                            }
                        }

                        $dayBlocked = $blockedRelation->where('day_of_week', $dayIndex)->contains(function ($slot) use ($toMinutes, $overlaps, $band) {
                            $s = $toMinutes($slot->start_time, 0);
                            $e = $toMinutes($slot->end_time, 0);

                            return $e > $s && $overlaps($band['start'], $band['end'], $s, $e);
                        });

                        if ($matchedRule !== null && $dayBlocked) {
                            $hasAnyBlocked = true;
                            $cellClass = 'bg-rose-100 text-rose-700 border border-rose-200';
                            $cellText = '✕';
                            $tooltip = $dayFullNames[$dayIndex].' · '.$band['full'].' : Bloqué';
                        } elseif ($matchedRule !== null) {
                            $cents = $matchedRule['hourly_price_cents'];
                            $cellClass = $cents === 0
                                ? 'bg-emerald-500 text-white'
                                : ($ratePaletteMap[$cents]['cell'] ?? 'bg-[#4338ca] text-white');
                            $cellText = $formatCompactPrice($cents);
                            $priceHuman = $cents === 0 ? 'Gratuit' : format_chf($cents / 100).'/h';
                            $ruleTitle = $matchedRule['label'] !== '' ? ' ('.$matchedRule['label'].')' : '';
                            $tooltip = $dayFullNames[$dayIndex].' · '.$band['full'].' : '.$priceHuman.$ruleTitle;
                        } else {
                            $cellClass = 'bg-slate-100 text-slate-400';
                            $cellText = '—';
                            $tooltip = $dayFullNames[$dayIndex].' · '.$band['full'].' : Fermé';
                        }
                    @endphp
                    <div
                        class="h-6 rounded flex items-center justify-center text-[10px] font-bold leading-none select-none cursor-help {{ $cellClass }}"
                        title="{{ $tooltip }}">
                        {{ $cellText }}
                    </div>
                @endforeach
            @endforeach
        </div>

        {{-- Légende tarifaire --}}
        <div class="mt-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-[10px] text-slate-600">
            @if($hasFreeBand)
                <span class="inline-flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-sm bg-emerald-500 inline-block"></span>
                    Gratuit
                </span>
            @endif
            @foreach($paidRates as $cents)
                <span class="inline-flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-sm {{ $ratePaletteMap[$cents]['dot'] }} inline-block"></span>
                    {{ format_chf($cents / 100) }}/h
                </span>
            @endforeach
            @if($hasAnyBlocked)
                <span class="inline-flex items-center gap-1">
                    <span class="w-2.5 h-2.5 rounded-sm bg-rose-200 border border-rose-300 inline-block"></span>
                    Bloqué
                </span>
            @endif
            <span class="inline-flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-sm bg-slate-200 inline-block"></span>
                Fermé
            </span>
        </div>
    </div>
@else
    @php
        $visualStartMinutes = $toMinutes($place->visual_day_start_time, 7 * 60);
        $visualEndMinutes = $toMinutes($place->visual_day_end_time, 19 * 60);
        if ($visualEndMinutes <= $visualStartMinutes) {
            $visualEndMinutes = min(1439, $visualStartMinutes + 60);
        }
        $visualSpanMinutes = max(60, $visualEndMinutes - $visualStartMinutes);

        $targetRows = 14;
        $slotStepMinutes = max(30, (int) ceil($visualSpanMinutes / $targetRows / 30) * 30);
        $slotCount = max(1, (int) ceil($visualSpanMinutes / $slotStepMinutes));

        $baseCents = (int) ($place->hourly_price_cents ?? 0);
        $isFreeBase = $baseCents === 0;
        $freeSlotClass = $isFreeBase ? 'bg-emerald-500' : 'bg-[#4338ca]';
        $basePriceLabel = $isFreeBase ? 'Gratuit' : format_chf($baseCents / 100).'/h';
    @endphp

    <div class="w-full max-w-sm">
        <div class="grid grid-cols-7 gap-1.5 px-3">
            @foreach($dayLabels as $dayIndex => $dayLabel)
                @php
                    $daySlots = $blockedRelation
                        ->where('day_of_week', $dayIndex)
                        ->map(function ($slot) use ($toMinutes, $visualStartMinutes) {
                            return [
                                'start' => $toMinutes($slot->start_time, $visualStartMinutes),
                                'end' => $toMinutes($slot->end_time, $visualStartMinutes),
                            ];
                        })
                        ->filter(fn ($slot) => $slot['end'] > $slot['start'])
                        ->values();

                    $blockedRanges = [];
                    foreach ($daySlots->sortBy('start') as $slot) {
                        $from = max($slot['start'], $visualStartMinutes);
                        $to = min($slot['end'], $visualEndMinutes);

                        if ($to <= $from) {
                            continue;
                        }

                        if (empty($blockedRanges) || $from > $blockedRanges[count($blockedRanges) - 1]['end']) {
                            $blockedRanges[] = ['start' => $from, 'end' => $to];
                        } else {
                            $blockedRanges[count($blockedRanges) - 1]['end'] = max($blockedRanges[count($blockedRanges) - 1]['end'], $to);
                        }
                    }

                    $freeRanges = [];
                    $cursor = $visualStartMinutes;
                    foreach ($blockedRanges as $range) {
                        if ($range['start'] > $cursor) {
                            $freeRanges[] = ['start' => $cursor, 'end' => $range['start']];
                        }
                        $cursor = max($cursor, $range['end']);
                    }
                    if ($cursor < $visualEndMinutes) {
                        $freeRanges[] = ['start' => $cursor, 'end' => $visualEndMinutes];
                    }

                    if (empty($freeRanges)) {
                        $tooltipText = $dayFullNames[$dayIndex].' : aucune plage libre';
                    } else {
                        $tooltipText = $dayFullNames[$dayIndex].' ('.$basePriceLabel.') : '.collect($freeRanges)
                            ->map(fn ($range) => $formatTime($range['start']).'–'.$formatTime($range['end']))
                            ->implode(' | ');
                    }
                @endphp
                <div class="flex flex-col items-center gap-1">
                    <span class="text-[10px] font-bold {{ $labelClass }}">{{ $dayLabel }}</span>
                    <div class="relative w-full {{ $heightClass }} rounded-md overflow-hidden border cursor-help bg-slate-100 border-[#dce3ed]" title="{{ $tooltipText }}">
                        <div class="absolute inset-0 grid gap-px" style="grid-template-rows: repeat({{ $slotCount }}, minmax(0, 1fr));">
                            @for($slotIndex = 0; $slotIndex < $slotCount; $slotIndex++)
                                @php
                                    $slotStart = $visualStartMinutes + ($slotIndex * $slotStepMinutes);
                                    $slotEnd = min($slotStart + $slotStepMinutes, $visualEndMinutes);
                                    $isBlocked = $daySlots->contains(fn ($blocked) => $overlaps($slotStart, $slotEnd, $blocked['start'], $blocked['end']));
                                @endphp
                                <div class="{{ $isBlocked ? 'bg-rose-400' : $freeSlotClass }}"></div>
                            @endfor
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-2 flex items-center justify-center gap-3 text-[10px] text-slate-600">
            <span class="inline-flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-sm {{ $freeSlotClass }} inline-block"></span>
                Libre · {{ $basePriceLabel }}
            </span>
            <span class="inline-flex items-center gap-1">
                <span class="w-2.5 h-2.5 rounded-sm bg-rose-400 inline-block"></span>
                Bloqué
            </span>
        </div>
    </div>
@endif
