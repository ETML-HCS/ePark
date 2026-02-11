<?php

namespace Tests\Unit;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use App\Policies\ReservationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ReservationPolicy $policy;
    private User $owner;
    private User $tenant;
    private User $admin;
    private User $otherUser;
    private Reservation $reservation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ReservationPolicy();

        $this->owner = User::factory()->create(['role' => 'proprietaire', 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $this->owner->id]);
        $place = Place::factory()->create(['user_id' => $this->owner->id, 'site_id' => $site->id]);

        $this->tenant = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);
        $this->admin = User::factory()->create(['role' => 'admin', 'onboarded' => true]);
        $this->otherUser = User::factory()->create(['role' => 'locataire', 'onboarded' => true]);

        $this->reservation = Reservation::factory()->create([
            'user_id' => $this->tenant->id,
            'place_id' => $place->id,
        ]);
    }

    // ─── view ────────────────────────────────────────────

    public function test_tenant_can_view_own_reservation(): void
    {
        $this->assertTrue($this->policy->view($this->tenant, $this->reservation));
    }

    public function test_owner_can_view_reservation_on_their_place(): void
    {
        $this->assertTrue($this->policy->view($this->owner, $this->reservation));
    }

    public function test_admin_can_view_any_reservation(): void
    {
        $this->assertTrue($this->policy->view($this->admin, $this->reservation));
    }

    public function test_other_user_cannot_view_reservation(): void
    {
        $this->assertFalse($this->policy->view($this->otherUser, $this->reservation));
    }

    // ─── validate ────────────────────────────────────────

    public function test_owner_can_validate(): void
    {
        $this->assertTrue($this->policy->validate($this->owner, $this->reservation));
    }

    public function test_tenant_cannot_validate(): void
    {
        $this->assertFalse($this->policy->validate($this->tenant, $this->reservation));
    }

    public function test_other_user_cannot_validate(): void
    {
        $this->assertFalse($this->policy->validate($this->otherUser, $this->reservation));
    }

    // ─── refuse ──────────────────────────────────────────

    public function test_owner_can_refuse(): void
    {
        $this->assertTrue($this->policy->refuse($this->owner, $this->reservation));
    }

    public function test_tenant_cannot_refuse(): void
    {
        $this->assertFalse($this->policy->refuse($this->tenant, $this->reservation));
    }

    // ─── cancel ──────────────────────────────────────────

    public function test_tenant_can_cancel_own_reservation(): void
    {
        $this->assertTrue($this->policy->cancel($this->tenant, $this->reservation));
    }

    public function test_admin_can_cancel_any_reservation(): void
    {
        $this->assertTrue($this->policy->cancel($this->admin, $this->reservation));
    }

    public function test_owner_cannot_cancel(): void
    {
        $this->assertFalse($this->policy->cancel($this->owner, $this->reservation));
    }

    public function test_other_user_cannot_cancel(): void
    {
        $this->assertFalse($this->policy->cancel($this->otherUser, $this->reservation));
    }

    // ─── pay ─────────────────────────────────────────────

    public function test_tenant_can_pay_own_reservation(): void
    {
        $this->assertTrue($this->policy->pay($this->tenant, $this->reservation));
    }

    public function test_owner_cannot_pay(): void
    {
        $this->assertFalse($this->policy->pay($this->owner, $this->reservation));
    }

    public function test_other_user_cannot_pay(): void
    {
        $this->assertFalse($this->policy->pay($this->otherUser, $this->reservation));
    }
}
