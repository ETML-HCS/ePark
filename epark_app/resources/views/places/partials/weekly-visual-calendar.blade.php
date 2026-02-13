@php
    $heightClass = $heightClass ?? 'h-20';
    $dayLabels = [1 => 'L', 2 => 'M', 3 => 'M', 4 => 'J', 5 => 'V', 6 => 'S', 0 => 'D'];

    $toMinutes = static function (?string $time, int $fallback): int {
        if (!$time || !str_contains($time, ':')) {
            return $fallback;
        }

        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return max(0, min(1439, ($hours * 60) + $minutes));
    };

    $visualStartMinutes = $toMinutes($place->visual_day_start_time, 7 * 60);
    $visualEndMinutes = $toMinutes($place->visual_day_end_time, 19 * 60);
    if ($visualEndMinutes <= $visualStartMinutes) {
        $visualEndMinutes = min(1439, $visualStartMinutes + 60);
    }
    $visualSpanMinutes = max(60, $visualEndMinutes - $visualStartMinutes);

    $targetRows = 14;
    $slotStepMinutes = max(30, (int) ceil($visualSpanMinutes / $targetRows / 30) * 30);
    $slotCount = max(1, (int) ceil($visualSpanMinutes / $slotStepMinutes));

    $overlaps = static function (int $aStart, int $aEnd, int $bStart, int $bEnd): bool {
        return $aStart < $bEnd && $aEnd > $bStart;
    };

    $formatTime = static function (int $minutes): string {
        $hours = (int) floor($minutes / 60);
        $mins = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $mins);
    };
@endphp

<div class="grid grid-cols-7 gap-1.5 px-3 w-full max-w-sm">
    @foreach($dayLabels as $dayIndex => $dayLabel)
        @php
            $daySlots = ($place->relationLoaded('blockedSlots') ? $place->blockedSlots : collect())
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
                $tooltipText = 'Libre : aucune plage';
            } else {
                $tooltipText = 'Libre : '.collect($freeRanges)
                    ->map(fn ($range) => $formatTime($range['start']).' - '.$formatTime($range['end']))
                    ->implode(' | ');
            }
        @endphp
        <div class="flex flex-col items-center gap-1">
            <span class="text-[10px] font-black text-white">{{ $dayLabel }}</span>
            <div class="relative w-full {{ $heightClass }} rounded-md overflow-hidden border cursor-help bg-emerald-200/90 border-emerald-100/80" title="{{ $tooltipText }}">
                <div class="absolute inset-0 grid gap-px" style="grid-template-rows: repeat({{ $slotCount }}, minmax(0, 1fr));">
                    @for($slotIndex = 0; $slotIndex < $slotCount; $slotIndex++)
                        @php
                            $slotStart = $visualStartMinutes + ($slotIndex * $slotStepMinutes);
                            $slotEnd = min($slotStart + $slotStepMinutes, $visualEndMinutes);
                            $isBlocked = $daySlots->contains(fn ($blocked) => $overlaps($slotStart, $slotEnd, $blocked['start'], $blocked['end']));
                        @endphp
                        <div class="{{ $isBlocked ? 'bg-red-500' : 'bg-emerald-500' }}"></div>
                    @endfor
                </div>
            </div>
        </div>
    @endforeach
</div>
