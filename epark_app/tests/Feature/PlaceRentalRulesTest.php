<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Reservation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlaceRentalRulesTest extends TestCase
{
    use RefreshDatabase;

    private function createOnboardedUser(string $role = 'proprietaire'): User
    {
        $user = User::factory()->create(['role' => $role, 'onboarded' => true]);
        $site = Site::factory()->create(['user_id' => $user->id]);
        $user->update(['favorite_site_id' => $site->id]);

        return $user;
    }

    public function test_owner_can_create_place_with_weekday_free_and_weekend_paid_rules(): void
    {
        $owner = $this->createOnboardedUser('proprietaire');
        $site = $owner->sites()->firstOrFail();

        $response = $this->actingAs($owner)->post('/places', [
            'site_id' => $site->id,
            'nom' => 'Place Mixte A1',
            'hourly_price' => '2.00',
            'cancel_deadline_hours' => 24,
            'rental_rules_present' => '1',
            'rental_rules' => [
                [
                    'label' => 'Semaine journée',
                    'days' => [1, 2, 3, 4, 5],
                    'segments' => ['matin_travail', 'aprem_travail', 'journee'],
                    'hourly_price' => '0.00',
                ],
                [
                    'label' => 'Week-end',
                    'days' => [6, 0],
                    'segments' => ['matin_travail', 'aprem_travail', 'journee'],
                    'hourly_price' => '2.50',
                ],
            ],
        ]);

        $response->assertRedirect(route('places.mes'));

        $place = Place::where('nom', 'Place Mixte A1')->firstOrFail();
        $this->assertTrue($place->hasRentalRules());
        $this->assertCount(2, $place->normalizedRentalRules());
        $this->assertSame(0, $place->hourly_price_cents);
        $this->assertSame('Gratuit – CHF 2.50/h', $place->priceSummaryLabel());

        $this->actingAs($owner)
            ->get(route('places.mes'))
            ->assertOk()
            ->assertSee('Gratuit – CHF 2.50/h')
            ->assertSee('Grille hebdomadaire des disponibilités et tarifs')
            ->assertSee('Grat.')
            ->assertSee('2.50')
            ->assertSee('Lun–Ven')
            ->assertSee('Sam–Dim');

        $this->get('/')
            ->assertOk()
            ->assertSee('Grille hebdomadaire des disponibilités et tarifs')
            ->assertSee('Gratuit – CHF 2.50/h');

        $this->actingAs($owner)
            ->get(route('places.create'))
            ->assertOk()
            ->assertSee('Aperçu visuel de la semaine');

        $this->actingAs($owner)
            ->get(route('places.blocked-slots.edit', $place->id))
            ->assertOk()
            ->assertSee('Plages de location')
            ->assertSee('Grille hebdomadaire des disponibilités et tarifs');
    }

    public function test_place_creation_rejects_more_than_five_rental_rules(): void
    {
        $owner = $this->createOnboardedUser('proprietaire');
        $site = $owner->sites()->firstOrFail();

        $rules = [];
        for ($i = 1; $i <= 6; $i++) {
            $rules[] = [
                'label' => 'Plage '.$i,
                'days' => [$i % 7],
                'segments' => ['journee'],
                'hourly_price' => '1.50',
            ];
        }

        $this->actingAs($owner)
            ->post('/places', [
                'site_id' => $site->id,
                'nom' => 'Place Trop de Plages',
                'hourly_price' => '1.50',
                'rental_rules_present' => '1',
                'rental_rules' => $rules,
            ])
            ->assertSessionHasErrors('rental_rules');

        $this->assertDatabaseMissing('places', ['nom' => 'Place Trop de Plages']);
    }

    public function test_rental_rules_control_availability_and_dynamic_pricing_by_day(): void
    {
        $owner = $this->createOnboardedUser('proprietaire');
        $tenant = $this->createOnboardedUser('locataire');
        $site = $owner->sites()->firstOrFail();

        $place = Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'is_active' => true,
            'hourly_price_cents' => 0,
            'rental_rules_json' => [
                [
                    'label' => 'Semaine journée',
                    'days' => [1, 2, 3, 4, 5],
                    'segments' => ['matin_travail', 'aprem_travail', 'journee'],
                    'hourly_price_cents' => 0,
                ],
                [
                    'label' => 'Week-end',
                    'days' => [6, 0],
                    'segments' => ['journee'],
                    'hourly_price_cents' => 250,
                ],
            ],
        ]);

        $nextMonday = Carbon::now()->next(Carbon::MONDAY);
        $nextSaturday = Carbon::now()->next(Carbon::SATURDAY);

        // Lundi : Matin/Après-midi/Journée ouverts gratuitement, Soir refusé
        $mondayAvail = $this->actingAs($tenant)
            ->getJson('/express/availability?date='.$nextMonday->toDateString())
            ->assertOk();

        $mondayPlace = collect($mondayAvail->json('places'))->firstWhere('id', $place->id);
        $this->assertNotNull($mondayPlace);
        $this->assertEqualsCanonicalizing(['matin_travail', 'aprem_travail', 'journee'], $mondayPlace['availableSegments']);
        $this->assertSame(0, $mondayPlace['quotes']['journee']);

        // Réservation lundi soir (hors plage) -> refusée
        $this->actingAs($tenant)
            ->postJson('/express/book', [
                'place_id' => $place->id,
                'date' => $nextMonday->toDateString(),
                'segment' => 'soir',
            ])
            ->assertStatus(422);

        // Réservation lundi journée -> gratuite (amount_cents = 0, paid = true)
        $this->actingAs($tenant)
            ->postJson('/express/book', [
                'place_id' => $place->id,
                'date' => $nextMonday->toDateString(),
                'segment' => 'journee',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'paid' => true]);

        $mondayReservation = Reservation::where('place_id', $place->id)
            ->whereDate('date_debut', $nextMonday->toDateString())
            ->firstOrFail();
        $this->assertSame(0, $mondayReservation->amount_cents);
        $this->assertSame('paid', $mondayReservation->payment_status);

        // Samedi : uniquement Journée ouverte à 2.50 CHF/h * 9.5h = 2375 cts
        $saturdayAvail = $this->actingAs($tenant)
            ->getJson('/express/availability?date='.$nextSaturday->toDateString())
            ->assertOk();

        $saturdayPlace = collect($saturdayAvail->json('places'))->firstWhere('id', $place->id);
        $this->assertNotNull($saturdayPlace);
        $this->assertSame(['journee'], $saturdayPlace['availableSegments']);
        $this->assertSame(2375, $saturdayPlace['quotes']['journee']);

        // Réservation samedi journée -> payante (pending, 2375 cts)
        $this->actingAs($tenant)
            ->postJson('/express/book', [
                'place_id' => $place->id,
                'date' => $nextSaturday->toDateString(),
                'segment' => 'journee',
            ])
            ->assertOk()
            ->assertJson(['success' => true, 'paid' => false]);

        $saturdayReservation = Reservation::where('place_id', $place->id)
            ->whereDate('date_debut', $nextSaturday->toDateString())
            ->firstOrFail();
        $this->assertSame(2375, $saturdayReservation->amount_cents);
        $this->assertSame('pending', $saturdayReservation->payment_status);
    }

    public function test_owner_can_update_and_clear_rental_rules(): void
    {
        $owner = $this->createOnboardedUser('proprietaire');
        $site = $owner->sites()->firstOrFail();
        $place = Place::factory()->create([
            'user_id' => $owner->id,
            'site_id' => $site->id,
            'hourly_price_cents' => 200,
            'rental_rules_json' => [
                [
                    'label' => 'Soirée',
                    'days' => [1, 2, 3, 4, 5],
                    'segments' => ['soir'],
                    'hourly_price_cents' => 100,
                ],
            ],
        ]);

        // Modifier vers 2 plages
        $this->actingAs($owner)
            ->put("/places/{$place->id}", [
                'nom' => $place->nom,
                'hourly_price' => '2.00',
                'rental_rules_present' => '1',
                'rental_rules' => [
                    [
                        'label' => 'Matin',
                        'days' => [1, 2, 3, 4, 5],
                        'segments' => ['matin_travail'],
                        'hourly_price' => '1.50',
                    ],
                ],
            ])
            ->assertRedirect(route('places.mes'));

        $place->refresh();
        $this->assertCount(1, $place->normalizedRentalRules());
        $this->assertSame(150, $place->hourly_price_cents);

        // Tout effacer pour revenir au mode tarif unique
        $this->actingAs($owner)
            ->put("/places/{$place->id}", [
                'nom' => $place->nom,
                'hourly_price' => '3.00',
                'rental_rules_present' => '1',
            ])
            ->assertRedirect(route('places.mes'));

        $place->refresh();
        $this->assertFalse($place->hasRentalRules());
        $this->assertSame(300, $place->hourly_price_cents);
    }
}
