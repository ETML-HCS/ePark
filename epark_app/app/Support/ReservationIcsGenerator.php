<?php

namespace App\Support;

use App\Models\Reservation;

final class ReservationIcsGenerator
{
    public static function generate(Reservation $reservation): string
    {
        $reservation->loadMissing(['place.site', 'user']);

        $place = $reservation->place;
        $site = $place?->site;
        $placeName = $place !== null ? $place->nom : 'Place de parking';
        $siteName = $site !== null ? $site->nom : 'ePark';
        $address = $site !== null ? $site->adresse : '';

        $startUtc = $reservation->date_debut->copy()->utc()->format('Ymd\THis\Z');
        $endUtc = $reservation->date_fin->copy()->utc()->format('Ymd\THis\Z');
        $stampUtc = now()->utc()->format('Ymd\THis\Z');

        $summary = self::escape("ePark · {$placeName} ({$siteName})");
        $location = self::escape(trim($siteName.($address !== '' ? ', '.$address : '')));

        $descriptionParts = [
            "Réservation ePark #{$reservation->id}",
            "Place : {$placeName}",
            "Site : {$siteName}",
            'Statut : '.$reservation->statut,
            'Montant : '.format_chf(($reservation->amount_cents ?? 0) / 100),
        ];
        if (! empty($reservation->vehicle_plate)) {
            $descriptionParts[] = 'Véhicule : '.$reservation->vehicle_plate;
        }
        if (! empty($reservation->owner_message)) {
            $descriptionParts[] = 'Message propriétaire : '.$reservation->owner_message;
        }
        $description = self::escape(implode("\n", $descriptionParts));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//athys technology//ePark//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:epark-reservation-{$reservation->id}@epark.athys.ch",
            "DTSTAMP:{$stampUtc}",
            "DTSTART:{$startUtc}",
            "DTEND:{$endUtc}",
            "SUMMARY:{$summary}",
            "LOCATION:{$location}",
            "DESCRIPTION:{$description}",
            'BEGIN:VALARM',
            'TRIGGER:-PT15M',
            'ACTION:DISPLAY',
            'DESCRIPTION:Votre créneau ePark commence dans 15 minutes',
            'END:VALARM',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines)."\r\n";
    }

    private static function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value
        );
    }
}
