<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Models\PlaceAvailability;
use App\Models\PlaceUnavailability;
use App\Models\Site;
use App\Models\User;
use App\Services\PlaceBlockedSlotsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaceBlockedSlotsServiceTest extends TestCase
{
    use RefreshDatabase;

    private PlaceBlockedSlotsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PlaceBlockedSlotsService();
    }

    private function createActivePlace(?int $userId = null): Place
    {
        $user = $userId ? User::find($userId) : User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);

        return Place::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'is_active' => true,
        ]);
    }

    public function test_get_available_places_returns_active_places(): void
    {
        $this->createActivePlace();
        $result = $this->service->getAvailablePlacesForDate(Carbon::tomorrow());

        $this->assertGreaterThanOrEqual(1, $result['places']->count());
    }

    public function test_get_available_places_excludes_user_places(): void
    {
        $owner = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $this->createActivePlace($owner->id);

        $result = $this->service->getAvailablePlacesForDate(Carbon::tomorrow(), $owner->id);

        $this->assertCount(0, $result['places']);
    }

    public function test_get_available_places_excludes_inactive_places(): void
    {
        $user = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        Place::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'is_active' => false,
        ]);

        $result = $this->service->getAvailablePlacesForDate(Carbon::tomorrow());
        $this->assertEmpty($result['places'], 'Inactive places should be excluded from available places');
        foreach ($result['places'] as $place) {
            $this->assertTrue($place->is_active);
        }
    }

    public function test_get_available_places_returns_place_hours(): void
    {
        $place = $this->createActivePlace();
        $result = $this->service->getAvailablePlacesForDate(Carbon::tomorrow());

        $this->assertArrayHasKey($place->id, $result['placeHours']);
        $this->assertNotEmpty($result['placeHours'][$place->id]);
    }

    public function test_compute_available_hours_returns_24_hours_no_constraints(): void
    {
        $place = $this->createActivePlace();
        $hours = $this->service->computeAvailableHours($place, Carbon::tomorrow());

        $this->assertCount(24, $hours);
        $this->assertEquals('00:00', $hours[0]);
        $this->assertEquals('23:00', $hours[23]);
    }

    public function test_compute_available_hours_returns_empty_for_full_day_block(): void
    {
        $place = $this->createActivePlace();
        $tomorrow = Carbon::tomorrow();

        PlaceUnavailability::factory()->fullDay()->create([
            'place_id' => $place->id,
            'date' => $tomorrow->toDateString(),
        ]);

        $place->load('unavailabilities');
        $hours = $this->service->computeAvailableHours($place, $tomorrow);
        $this->assertEmpty($hours);
    }

    public function test_compute_available_hours_excludes_blocked_slots(): void
    {
        $place = $this->createActivePlace();
        $tomorrow = Carbon::tomorrow();
        $day = (int) $tomorrow->dayOfWeek;

        PlaceAvailability::factory()->create([
            'place_id' => $place->id,
            'day_of_week' => $day,
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $place->load('availabilities');
        $hours = $this->service->computeAvailableHours($place, $tomorrow);

        $this->assertNotContains('10:00', $hours);
        $this->assertNotContains('11:00', $hours);
        $this->assertContains('09:00', $hours);
        $this->assertContains('12:00', $hours);
    }

    public function test_compute_available_hours_excludes_partial_exception(): void
    {
        $place = $this->createActivePlace();
        $tomorrow = Carbon::tomorrow();

        PlaceUnavailability::factory()->partial('14:00', '16:00')->create([
            'place_id' => $place->id,
            'date' => $tomorrow->toDateString(),
        ]);

        $place->load('unavailabilities');
        $hours = $this->service->computeAvailableHours($place, $tomorrow);

        $this->assertNotContains('14:00', $hours);
        $this->assertNotContains('15:00', $hours);
        $this->assertContains('13:00', $hours);
        $this->assertContains('16:00', $hours);
    }

    public function test_compute_available_hours_returns_empty_when_place_not_available(): void
    {
        $place = $this->createActivePlace();
        $place->update(['availability_start_date' => Carbon::today()->addWeek()]);

        $hours = $this->service->computeAvailableHours($place, Carbon::tomorrow());
        $this->assertEmpty($hours);
    }

    public function test_find_first_hour_in_segment_matin(): void
    {
        $hours = ['08:00', '09:00', '10:00', '11:00', '12:00'];
        $result = $this->service->findFirstHourInSegment($hours, 'matin_travail');

        $this->assertEquals('08:00', $result);
    }

    public function test_find_first_hour_in_segment_aprem(): void
    {
        $hours = ['12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
        $result = $this->service->findFirstHourInSegment($hours, 'aprem_travail');

        $this->assertEquals('12:00', $result);
    }

    public function test_find_first_hour_in_segment_soir(): void
    {
        $hours = ['18:00', '19:00', '20:00', '21:00', '22:00', '23:00'];
        $result = $this->service->findFirstHourInSegment($hours, 'soir');

        $this->assertEquals('18:00', $result);
    }

    public function test_find_first_hour_in_segment_nuit(): void
    {
        $hours = ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00'];
        $result = $this->service->findFirstHourInSegment($hours, 'nuit');

        $this->assertEquals('00:00', $result);
    }

    public function test_find_first_hour_in_segment_returns_null_for_invalid(): void
    {
        $hours = ['08:00', '09:00'];
        $result = $this->service->findFirstHourInSegment($hours, 'invalid_segment');

        $this->assertNull($result);
    }

    public function test_find_first_hour_in_segment_returns_null_when_no_match(): void
    {
        $hours = ['18:00', '19:00'];
        $result = $this->service->findFirstHourInSegment($hours, 'matin_travail');

        $this->assertNull($result);
    }
}
