<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\PlaceUnavailability;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createOnboardedUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'onboarded' => true,
            'role' => 'locataire',
        ], $attributes));

        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);

        return $user;
    }

    /**
     * 1. Vérification du cloisonnement des rôles (RoleMiddleware).
     * Le rôle "les deux" (locataire + propriétaire) ne doit PAS donner accès aux routes admin.
     */
    public function test_user_with_role_les_deux_cannot_access_admin_dashboard_or_stats(): void
    {
        $dualUser = $this->createOnboardedUser(['role' => 'les deux']);

        $resDashboard = $this->actingAs($dualUser)->get(route('admin.dashboard'));
        $resDashboard->assertStatus(403);

        $resStats = $this->actingAs($dualUser)->get(route('admin.stats'));
        $resStats->assertStatus(403);
    }

    /**
     * 2. Les visiteurs non authentifiés sont systématiquement redirigés vers le login.
     */
    public function test_guests_cannot_access_protected_endpoints(): void
    {
        $protectedRoutes = [
            '/dashboard',
            '/admin',
            '/admin/stats',
            '/places',
            '/places/create',
            '/reservations',
            '/reservations/create',
            '/sites',
            '/profile',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    /**
     * 3. Protection contre les failles IDOR sur la gestion des places.
     * L'utilisateur A ne peut ni modifier ni supprimer une place appartenant à l'utilisateur B.
     */
    public function test_user_cannot_edit_or_delete_another_users_place(): void
    {
        $userA = $this->createOnboardedUser(['name' => 'User A']);
        $userB = $this->createOnboardedUser(['name' => 'User B']);

        $placeB = Place::factory()->create([
            'user_id' => $userB->id,
            'site_id' => $userB->favorite_site_id,
            'nom' => 'Place B Protégée',
            'hourly_price_cents' => 200,
        ]);

        // Tentative d'accès au formulaire d'édition
        $this->actingAs($userA)
            ->get(route('places.edit', $placeB))
            ->assertStatus(403);

        // Tentative de modification directe
        $this->actingAs($userA)
            ->put(route('places.update', $placeB), [
                'nom' => 'Piraté par User A',
                'hourly_price' => 0.01,
            ])
            ->assertStatus(403);

        $placeB->refresh();
        $this->assertSame('Place B Protégée', $placeB->nom);

        // Tentative de suppression
        $this->actingAs($userA)
            ->delete(route('places.destroy', $placeB))
            ->assertStatus(403);

        $this->assertDatabaseHas('places', ['id' => $placeB->id]);
    }

    /**
     * 4. Protection contre la manipulation des indisponibilités d'autrui.
     */
    public function test_user_cannot_tamper_with_unavailabilities_of_another_users_place(): void
    {
        $userA = $this->createOnboardedUser(['name' => 'User A']);
        $userB = $this->createOnboardedUser(['name' => 'User B']);

        $placeB = Place::factory()->create([
            'user_id' => $userB->id,
            'site_id' => $userB->favorite_site_id,
        ]);

        $unavailability = PlaceUnavailability::create([
            'place_id' => $placeB->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        // User A tente de supprimer l'indisponibilité de la place de User B
        $this->actingAs($userA)
            ->delete(route('places.unavailability.destroy', [
                'place' => $placeB,
                'unavailability' => $unavailability,
            ]))
            ->assertStatus(403);

        $this->assertDatabaseHas('place_unavailabilities', ['id' => $unavailability->id]);
    }

    /**
     * 5. Protection contre les failles IDOR sur les réservations.
     * User A ne peut ni voir, ni payer, ni modifier, ni annuler la réservation de User B.
     */
    public function test_user_cannot_access_or_pay_another_users_reservation(): void
    {
        $renterB = $this->createOnboardedUser(['name' => 'Locataire B']);
        $attackerA = $this->createOnboardedUser(['name' => 'Attaquant A']);
        $owner = $this->createOnboardedUser(['name' => 'Propriétaire']);

        $place = Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $owner->favorite_site_id,
        ]);

        $reservationB = Reservation::factory()->create([
            'user_id' => $renterB->id,
            'place_id' => $place->id,
            'statut' => 'en_attente',
            'payment_status' => 'pending',
            'amount_cents' => 1500,
            'date_debut' => now()->addDays(3)->setTime(8, 0),
            'date_fin' => now()->addDays(3)->setTime(12, 0),
        ]);

        // 1. Voir la réservation
        $this->actingAs($attackerA)
            ->get(route('reservations.show', $reservationB))
            ->assertStatus(403);

        // 2. Payer à la place de l'utilisateur
        $this->actingAs($attackerA)
            ->post(route('reservations.payer', $reservationB))
            ->assertStatus(403);

        // 3. Modifier la réservation
        $this->actingAs($attackerA)
            ->put(route('reservations.update', $reservationB), [
                'date' => now()->addDays(4)->toDateString(),
                'segment' => 'matin_travail',
            ])
            ->assertStatus(403);

        // 4. Annuler la réservation
        $this->actingAs($attackerA)
            ->delete(route('reservations.destroy', $reservationB))
            ->assertStatus(403);

        $reservationB->refresh();
        $this->assertSame('en_attente', $reservationB->statut);
        $this->assertSame('pending', $reservationB->payment_status);
    }

    /**
     * 6. Protection contre l'élévation de privilèges via Mass Assignment sur le profil.
     */
    public function test_user_cannot_escalate_role_via_profile_update(): void
    {
        $user = $this->createOnboardedUser(['role' => 'locataire']);

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Tentative Piratage',
                'email' => $user->email,
                'role' => 'admin',
            ]);

        $user->refresh();
        $this->assertSame('locataire', $user->role);
        $this->assertNotSame('admin', $user->role);
    }

    /**
     * 7. Résistance aux injections SQL dans les paramètres de dates / filtres.
     */
    public function test_sql_injection_payloads_do_not_compromise_queries(): void
    {
        $user = $this->createOnboardedUser();

        $payloads = [
            "' OR '1'='1",
            '1; DROP TABLE users; --',
            "2026-02-01' UNION SELECT * FROM users --",
        ];

        foreach ($payloads as $payload) {
            $response = $this->actingAs($user)->get(route('reservations.index', [
                'date' => $payload,
                'status' => $payload,
            ]));

            // L'application doit renvoyer 200 avec liste vide ou validation propre, jamais d'erreur 500 SQL
            $this->assertNotSame(500, $response->status());
        }
    }

    /**
     * 8. Protection XSS : les entrées utilisateurs sont échappées dans le HTML.
     */
    public function test_xss_script_tags_are_escaped_in_rendered_views(): void
    {
        $user = $this->createOnboardedUser(['name' => '<script>alert("xss-user")</script>']);
        $site = Site::factory()->create([
            'user_id' => $user->id,
            'nom' => '<script>alert("xss-site")</script>',
        ]);

        $place = Place::factory()->create([
            'user_id' => $user->id,
            'site_id' => $site->id,
            'nom' => '<script>alert("xss-place")</script>',
        ]);

        $response = $this->actingAs($user)->get(route('places.mes'));
        $response->assertStatus(200);

        // La balise script ne doit pas apparaître brute non-échappée
        $response->assertDontSee('<script>alert("xss-place")</script>', false);
        // Elle doit être convenablement convertie en entités HTML
        $response->assertSee('&lt;script&gt;alert(&quot;xss-place&quot;)&lt;/script&gt;', false);
    }

    /**
     * 9. Sécurité des clés ePark Express : une clé invalide échoue sans révéler d'information.
     */
    public function test_invalid_express_key_fails_cleanly(): void
    {
        $response = $this->post(route('express.login'), [
            'express_key' => 'epk_fake_invalid_key_999999999999',
        ]);

        $response->assertSessionHasErrors('express_key');
        $this->assertGuest();
    }
}
