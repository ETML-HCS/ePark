<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function onboardedUser(): User
    {
        $user = User::factory()->create(['onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);
        return $user;
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = $this->onboardedUser();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = $this->onboardedUser();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = $this->onboardedUser();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = $this->onboardedUser();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = $this->onboardedUser();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_user_can_add_valid_secret_group_from_profile(): void
    {
        $owner = $this->onboardedUser();
        $ownerSite = Site::factory()->create(['user_id' => $owner->id]);

        Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $ownerSite->id,
            'is_group_reserved' => true,
            'group_name' => 'ETML/CFPV',
            'group_access_code_hash' => Hash::make('etml3865'),
        ]);

        $member = $this->onboardedUser();

        $response = $this->actingAs($member)->post('/profile/secret-groups', [
            'secret_group_name' => 'ETML/CFPV',
            'secret_group_code' => 'etml3865',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('status', 'secret-group-added');

        $member->refresh();
        $this->assertContains('etml3865', $member->normalizedSecretGroupCodes());
    }

    public function test_user_cannot_add_invalid_secret_group_pair(): void
    {
        $member = $this->onboardedUser();

        $response = $this->actingAs($member)->post('/profile/secret-groups', [
            'secret_group_name' => 'ETML/CFPV',
            'secret_group_code' => 'wrong-code',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHasErrorsIn('secretGroup', ['secret_group_code']);
    }

    public function test_user_can_remove_secret_group_from_profile(): void
    {
        $member = $this->onboardedUser();
        $member->update([
            'secret_group_codes' => [
                ['name' => 'ETML/CFPV', 'code' => 'etml3865'],
            ],
        ]);

        $response = $this->actingAs($member)->delete('/profile/secret-groups', [
            'secret_group_code_remove' => 'etml3865',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('status', 'secret-group-removed');

        $member->refresh();
        $this->assertNotContains('etml3865', $member->normalizedSecretGroupCodes());
    }
}
