<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Feedback;
use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    // ─── Rôles ───────────────────────────────────────────

    public function test_is_admin_returns_true_for_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_for_non_admin(): void
    {
        $user = User::factory()->create(['role' => 'locataire']);
        $this->assertFalse($user->isAdmin());
    }

    public function test_is_owner_returns_true_for_proprietaire(): void
    {
        $user = User::factory()->create(['role' => 'proprietaire']);
        $this->assertTrue($user->isOwner());
    }

    public function test_is_owner_returns_true_for_les_deux(): void
    {
        $user = User::factory()->create(['role' => 'les deux']);
        $this->assertTrue($user->isOwner());
    }

    public function test_is_owner_returns_false_for_locataire(): void
    {
        $user = User::factory()->create(['role' => 'locataire']);
        $this->assertFalse($user->isOwner());
    }

    public function test_is_tenant_returns_true_for_locataire(): void
    {
        $user = User::factory()->create(['role' => 'locataire']);
        $this->assertTrue($user->isTenant());
    }

    public function test_is_tenant_returns_true_for_les_deux(): void
    {
        $user = User::factory()->create(['role' => 'les deux']);
        $this->assertTrue($user->isTenant());
    }

    public function test_is_tenant_returns_false_for_proprietaire(): void
    {
        $user = User::factory()->create(['role' => 'proprietaire']);
        $this->assertFalse($user->isTenant());
    }

    public function test_can_reserve_for_locataire(): void
    {
        $user = User::factory()->create(['role' => 'locataire']);
        $this->assertTrue($user->canReserve());
    }

    public function test_can_reserve_for_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($user->canReserve());
    }

    public function test_can_reserve_false_for_proprietaire(): void
    {
        $user = User::factory()->create(['role' => 'proprietaire']);
        $this->assertFalse($user->canReserve());
    }

    public function test_can_offer_for_proprietaire(): void
    {
        $user = User::factory()->create(['role' => 'proprietaire']);
        $this->assertTrue($user->canOffer());
    }

    public function test_can_offer_for_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->assertTrue($user->canOffer());
    }

    public function test_can_offer_false_for_locataire(): void
    {
        $user = User::factory()->create(['role' => 'locataire']);
        $this->assertFalse($user->canOffer());
    }

    // ─── Relations ───────────────────────────────────────

    public function test_user_has_many_places(): void
    {
        $user = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        Place::factory()->count(3)->create(['user_id' => $user->id, 'site_id' => $site->id]);

        $this->assertCount(3, $user->places);
    }

    public function test_user_has_many_sites(): void
    {
        $user = User::factory()->create();
        Site::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->sites);
    }

    public function test_user_belongs_to_favorite_site(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);

        $this->assertEquals($site->id, $user->favoriteSite->id);
    }

    public function test_user_has_many_reservations(): void
    {
        $user = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $place = Place::factory()->create(['user_id' => $user->id, 'site_id' => $site->id]);
        Reservation::factory()->count(2)->create(['user_id' => $user->id, 'place_id' => $place->id]);

        $this->assertCount(2, $user->reservations);
    }

    public function test_user_has_many_feedbacks(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->create(['user_id' => $user->id]);
        $place = Place::factory()->create(['user_id' => $user->id, 'site_id' => $site->id]);
        $reservation = Reservation::factory()->create(['user_id' => $user->id, 'place_id' => $place->id]);
        Feedback::factory()->create(['user_id' => $user->id, 'reservation_id' => $reservation->id]);

        $this->assertCount(1, $user->feedbacks);
    }

    public function test_user_has_many_audit_logs(): void
    {
        $user = User::factory()->create();
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'test',
            'entity_type' => 'User',
            'entity_id' => $user->id,
            'before' => [],
            'after' => [],
        ]);

        $this->assertCount(1, $user->auditLogs);
    }

    // ─── Casts ───────────────────────────────────────────

    public function test_onboarded_is_cast_to_boolean(): void
    {
        $user = User::factory()->create(['onboarded' => 1]);
        $this->assertIsBool($user->onboarded);
        $this->assertTrue($user->onboarded);
    }

    public function test_email_verified_at_is_cast_to_datetime(): void
    {
        $user = User::factory()->create();
        $this->assertInstanceOf(\DateTimeInterface::class, $user->email_verified_at);
    }
}
