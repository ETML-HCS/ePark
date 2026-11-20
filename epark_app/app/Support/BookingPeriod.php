<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

final class BookingPeriod
{
    public const SEGMENTS = [
        'matin_travail' => ['label' => 'Matin', 'hours' => '08:00–12:00', 'start' => 480, 'end' => 720],
        'aprem_travail' => ['label' => 'Après-midi', 'hours' => '12:00–17:30', 'start' => 720, 'end' => 1050],
        'journee' => ['label' => 'Journée', 'hours' => '08:00–17:30', 'start' => 480, 'end' => 1050],
        'soir' => ['label' => 'Soir', 'hours' => '18:00–00:00', 'start' => 1080, 'end' => 1440],
        'nuit' => ['label' => 'Nuit', 'hours' => '00:00–07:30', 'start' => 0, 'end' => 450],
    ];

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function range(Carbon $date, string $segment): array
    {
        $period = self::SEGMENTS[$segment] ?? throw new InvalidArgumentException('Moment de la journée invalide.');

        return [
            $date->copy()->startOfDay()->setTime(intdiv($period['start'], 60), $period['start'] % 60),
            $period['end'] === 1440
                ? $date->copy()->endOfDay()
                : $date->copy()->startOfDay()->setTime(intdiv($period['end'], 60), $period['end'] % 60),
        ];
    }

    public static function amount(int $hourlyCents, string $segment): int
    {
        $period = self::SEGMENTS[$segment] ?? throw new InvalidArgumentException('Moment de la journée invalide.');

        return (int) round($hourlyCents * ($period['end'] - $period['start']) / 60);
    }

    public static function detectSegment(Carbon $start, Carbon $end): string
    {
        $startMinutes = ((int) $start->format('H')) * 60 + (int) $start->format('i');
        $endMinutes = ((int) $end->format('H')) * 60 + (int) $end->format('i');

        if ($startMinutes === 480 && $endMinutes >= 1020) {
            return 'journee';
        }

        return match (true) {
            $startMinutes >= 480 && $startMinutes < 720 => 'matin_travail',
            $startMinutes >= 720 && $startMinutes < 1050 => 'aprem_travail',
            $startMinutes >= 1080 && $startMinutes < 1440 => 'soir',
            default => 'nuit',
        };
    }

    public static function validationRule(): string
    {
        return 'required|in:'.implode(',', array_keys(self::SEGMENTS));
    }
}
