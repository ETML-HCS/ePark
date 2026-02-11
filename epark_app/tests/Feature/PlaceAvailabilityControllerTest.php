<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\PlaceAvailability;
use App\Models\PlaceUnavailability;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaceAvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Place $place;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $this->owner->id]);
        $this->owner->update(['favorite_site_id' => $site->id]);
        $this->place = Place::factory()->create(['user_id' => $this->owner->id, 'site_id' => $site->id]);
    }

    public function test_owner_can_see_availability_edit(): void
    {
        $response = $this->actingAs($this->owner)
            ->get("/places/{$this->place->id}/availability");
        $response->assertStatus(200);
    }

    public function test_owner_can_update_availability(): void
    {
        $response = $this->actingAs($this->owner)
            ->post("/places/{$this->place->id}/availability", [
                'slots' => [
                    ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '12:00'],
                    ['day_of_week' => 1, 'start_time' => '14:00', 'end_time' => '18:00'],
                ],
            ]);

        $response->assertRedirect();
    }

    public function test_owner_can_add_exception(): void
    {
        $response = $this->actingAs($this->owner)
            ->post("/places/{$this->place->id}/unavailability", [
                'date' => Carbon::tomorrow()->format('Y-m-d'),
                'reason' => 'Maintenance',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('place_unavailabilities', [
            'place_id' => $this->place->id,
            'reason' => 'Maintenance',
        ]);
    }

    public function test_owner_can_delete_exception(): void
    {
        $exception = PlaceUnavailability::factory()->create([
            'place_id' => $this->place->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("/places/{$this->place->id}/unavailability/{$exception->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('place_unavailabilities', ['id' => $exception->id]);
    }

    public function test_guest_cannot_access_availability(): void
    {
        $response = $this->get("/places/{$this->place->id}/availability");
        $response->assertRedirect('/login');
    }
}
