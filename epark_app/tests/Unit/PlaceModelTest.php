<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Models\PlaceAvailability;
use App\Models\PlaceUnavailability;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaceModelTest extends TestCase
{
    use RefreshDatabase;

    private function createPlace(array $attributes = []): Place
    {
        $user = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);

        return Place::factory()->create(array_merge([
            'user_id' => $user->id,
            'site_id' => $site->id,
        ], $attributes));
    }

    // ─── Relations ───────────────────────────────────────

    public function test_place_belongs_to_user(): void
    {
        $place = $this->createPlace();
        $this->assertInstanceOf(User::class, $place->user);
    }

    public function test_place_belongs_to_site(): void
    {
        $place = $this->createPlace();
        $this->assertInstanceOf(Site::class, $place->site);
    }

    public function test_place_has_many_availabilities(): void
    {
        $place = $this->createPlace();
        PlaceAvailability::factory()->count(3)->create(['place_id' => $place->id]);

        $this->assertCount(3, $place->availabilities);
    }

    public function test_place_has_many_unavailabilities(): void
    {
        $place = $this->createPlace();
        PlaceUnavailability::factory()->count(2)->create(['place_id' => $place->id]);

        $this->assertCount(2, $place->unavailabilities);
    }

    // ─── Casts ───────────────────────────────────────────

    public function test_dimensions_json_cast_to_array(): void
    {
        $place = $this->createPlace(['dimensions_json' => ['longueur' => 5]]);
        $this->assertIsArray($place->dimensions_json);
        $this->assertEquals(5, $place->dimensions_json['longueur']);
    }

    public function test_hourly_price_cents_cast_to_integer(): void
    {
        $place = $this->createPlace(['hourly_price_cents' => 500]);
        $this->assertIsInt($place->hourly_price_cents);
    }

    public function test_is_active_cast_to_boolean(): void
    {
        $place = $this->createPlace(['is_active' => 1]);
        $this->assertIsBool($place->is_active);
    }

    // ─── isAvailableFor() ────────────────────────────────

    public function test_is_available_for_returns_true_when_no_constraints(): void
    {
        $place = $this->createPlace();
        $start = Carbon::tomorrow()->setTime(10, 0);
        $end = Carbon::tomorrow()->setTime(12, 0);

        $this->assertTrue($place->isAvailableFor($start, $end));
    }

    public function test_is_available_for_returns_false_before_start_date(): void
    {
        $place = $this->createPlace([
            'availability_start_date' => Carbon::today()->addDays(5),
        ]);
        $start = Carbon::tomorrow()->setTime(10, 0);
        $end = Carbon::tomorrow()->setTime(12, 0);

        $this->assertFalse($place->isAvailableFor($start, $end));
    }

    public function test_is_available_for_returns_false_after_end_date(): void
    {
        $place = $this->createPlace([
            'availability_end_date' => Carbon::yesterday(),
        ]);
        $start = Carbon::tomorrow()->setTime(10, 0);
        $end = Carbon::tomorrow()->setTime(12, 0);

        $this->assertFalse($place->isAvailableFor($start, $end));
    }

    public function test_is_available_for_returns_false_for_multi_day(): void
    {
        $place = $this->createPlace();
        $start = Carbon::tomorrow()->setTime(10, 0);
        $end = Carbon::tomorrow()->addDay()->setTime(12, 0);

        $this->assertFalse($place->isAvailableFor($start, $end));
    }

    public function test_is_available_for_returns_false_when_blocked_by_availability(): void
    {
        $place = $this->createPlace();
        $tomorrow = Carbon::tomorrow();
        $day = (int) $tomorrow->dayOfWeek;

        PlaceAvailability::factory()->create([
            'place_id' => $place->id,
            'day_of_week' => $day,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ]);

        $start = $tomorrow->copy()->setTime(10, 0);
        $end = $tomorrow->copy()->setTime(12, 0);

        $this->assertFalse($place->isAvailableFor($start, $end));
    }

    public function test_is_available_for_returns_false_when_full_day_exception(): void
    {
        $place = $this->createPlace();
        $tomorrow = Carbon::tomorrow();

        PlaceUnavailability::factory()->fullDay()->create([
            'place_id' => $place->id,
            'date' => $tomorrow->toDateString(),
        ]);

        $start = $tomorrow->copy()->setTime(10, 0);
        $end = $tomorrow->copy()->setTime(12, 0);

        $this->assertFalse($place->isAvailableFor($start, $end));
    }

    public function test_is_available_for_returns_false_when_partial_exception_overlaps(): void
    {
        $place = $this->createPlace();
        $tomorrow = Carbon::tomorrow();

        PlaceUnavailability::factory()->partial('09:00', '11:00')->create([
            'place_id' => $place->id,
            'date' => $tomorrow->toDateString(),
        ]);

        $start = $tomorrow->copy()->setTime(10, 0);
        $end = $tomorrow->copy()->setTime(12, 0);

        $this->assertFalse($place->isAvailableFor($start, $end));
    }

    public function test_is_available_for_returns_true_when_exception_does_not_overlap(): void
    {
        $place = $this->createPlace();
        $tomorrow = Carbon::tomorrow();

        PlaceUnavailability::factory()->partial('14:00', '16:00')->create([
            'place_id' => $place->id,
            'date' => $tomorrow->toDateString(),
        ]);

        $start = $tomorrow->copy()->setTime(10, 0);
        $end = $tomorrow->copy()->setTime(12, 0);

        $this->assertTrue($place->isAvailableFor($start, $end));
    }

    // ─── hasAvailabilityForDate() ────────────────────────

    public function test_has_availability_for_date_returns_true_no_constraints(): void
    {
        $place = $this->createPlace();
        $this->assertTrue($place->hasAvailabilityForDate(Carbon::tomorrow()));
    }

    public function test_has_availability_for_date_returns_false_before_start(): void
    {
        $place = $this->createPlace([
            'availability_start_date' => Carbon::today()->addWeek(),
        ]);
        $this->assertFalse($place->hasAvailabilityForDate(Carbon::tomorrow()));
    }

    public function test_has_availability_for_date_returns_false_when_full_day_block(): void
    {
        $place = $this->createPlace();
        $tomorrow = Carbon::tomorrow();

        PlaceUnavailability::factory()->fullDay()->create([
            'place_id' => $place->id,
            'date' => $tomorrow->toDateString(),
        ]);

        $this->assertFalse($place->hasAvailabilityForDate($tomorrow));
    }

    public function test_has_availability_for_date_returns_true_for_partial_block(): void
    {
        $place = $this->createPlace();
        $tomorrow = Carbon::tomorrow();

        PlaceUnavailability::factory()->partial('10:00', '12:00')->create([
            'place_id' => $place->id,
            'date' => $tomorrow->toDateString(),
        ]);

        // Partial block should not block the whole day
        $this->assertTrue($place->hasAvailabilityForDate($tomorrow));
    }
}
