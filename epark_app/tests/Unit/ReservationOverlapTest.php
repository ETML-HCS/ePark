<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Site;
use App\Models\Place;
use Illuminate\Support\Carbon;

class ReservationOverlapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // RefreshDatabase trait will run migrations automatically; create fixtures below
        $this->user = User::factory()->create();
        $this->site = Site::create([
            'nom' => 'Test Site',
            'adresse' => '1 rue de Test',
            'user_id' => $this->user->id,
        ]);
        $this->place = Place::create([
            'site_id' => $this->site->id,
            'user_id' => $this->user->id,
            'nom' => 'A-1',
            'is_active' => true,
            'caracteristiques' => null,
        ]);
    }

    public function test_no_overlap_for_adjacent_slots()
    {
        $start = Carbon::parse('2026-01-29 10:00');
        $end = $start->copy()->addHour(); // 11:00

        Reservation::create([
            'user_id' => $this->user->id,
            'place_id' => $this->place->id,
            'date_debut' => $start,
            'date_fin' => $end,
            'statut' => 'confirmée',
            'battement_minutes' => 0,
            'paiement_effectue' => true,
        ]);

        $newStart = Carbon::parse('2026-01-29 11:00');
        $newEnd = $newStart->copy()->addHour();

        $this->assertFalse(Reservation::overlaps($this->place->id, $newStart, $newEnd));
    }

    public function test_overlap_detected_for_overlapping_slot()
    {
        $start = Carbon::parse('2026-01-29 10:00');
        $end = $start->copy()->addHour();

        Reservation::create([
            'user_id' => $this->user->id,
            'place_id' => $this->place->id,
            'date_debut' => $start,
            'date_fin' => $end,
            'statut' => 'confirmée',
            'battement_minutes' => 0,
            'paiement_effectue' => true,
        ]);

        $newStart = Carbon::parse('2026-01-29 10:30');
        $newEnd = $newStart->copy()->addHour();

        $this->assertTrue(Reservation::overlaps($this->place->id, $newStart, $newEnd));
    }

    public function test_battement_on_existing_blocks_following()
    {
        $start = Carbon::parse('2026-01-29 10:00');
        $end = $start->copy()->addHour();

        Reservation::create([
            'user_id' => $this->user->id,
            'place_id' => $this->place->id,
            'date_debut' => $start,
            'date_fin' => $end,
            'statut' => 'confirmée',
            'battement_minutes' => 15,
            'paiement_effectue' => true,
        ]);

        $newStart = Carbon::parse('2026-01-29 11:00');
        $newEnd = $newStart->copy()->addHour();

        // Because existing reservation has battement 15, effective end 11:15 -> overlap
        $this->assertTrue(Reservation::overlaps($this->place->id, $newStart, $newEnd));
    }

    public function test_new_battement_can_cause_conflict()
    {
        $start = Carbon::parse('2026-01-29 11:00');
        $end = $start->copy()->addHour();

        Reservation::create([
            'user_id' => $this->user->id,
            'place_id' => $this->place->id,
            'date_debut' => $start,
            'date_fin' => $end,
            'statut' => 'confirmée',
            'battement_minutes' => 0,
            'paiement_effectue' => true,
        ]);

        $newStart = Carbon::parse('2026-01-29 10:00');
        $newEnd = $newStart->copy()->addHour();

        // New reservation requests 15 minutes battement -> new end 11:15 -> overlap
        $this->assertTrue(Reservation::overlaps($this->place->id, $newStart, $newEnd, 15));
    }
}
