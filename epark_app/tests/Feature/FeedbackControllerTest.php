<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeedbackControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $owner->id]);
        $owner->update(['favorite_site_id' => $site->id]);

        $place = Place::factory()->create(['user_id' => $owner->id, 'site_id' => $site->id]);

        $this->tenant = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $tenantSite = Site::factory()->create(['user_id' => $this->tenant->id]);
        $this->tenant->update(['favorite_site_id' => $tenantSite->id]);

        $this->reservation = Reservation::factory()->confirmed()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $place->id,
            'date_fin' => Carbon::yesterday(),
        ]);
    }

    public function test_tenant_can_see_feedback_form(): void
    {
        $response = $this->actingAs($this->tenant)
            ->get("/reservations/{$this->reservation->id}/feedback");
        $response->assertStatus(200);
    }

    public function test_tenant_can_submit_feedback(): void
    {
        $response = $this->actingAs($this->tenant)
            ->post("/reservations/{$this->reservation->id}/feedback", [
                'rating' => 4,
                'comment' => 'Très bien!',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('feedbacks', [
            'reservation_id' => $this->reservation->id,
            'user_id' => $this->tenant->id,
            'rating' => 4,
        ]);
    }

    public function test_feedback_requires_rating(): void
    {
        $response = $this->actingAs($this->tenant)
            ->post("/reservations/{$this->reservation->id}/feedback", [
                'comment' => 'Pas de note',
            ]);

        $response->assertSessionHasErrors('rating');
    }

    public function test_guest_cannot_leave_feedback(): void
    {
        $response = $this->post("/reservations/{$this->reservation->id}/feedback", [
            'rating' => 5,
        ]);

        $response->assertRedirect('/login');
    }
}
