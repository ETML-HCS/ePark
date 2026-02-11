<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin = User::updateOrCreate(
            ['email' => 'admin@epark.local'],
            [
                'name' => 'Admin ePark',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
            ]
        );

        $initialSites = [
            ['nom' => 'Centre-ville', 'adresse' => '1 Rue Centrale'],
            ['nom' => 'Gare', 'adresse' => '10 Avenue de la Gare'],
            ['nom' => 'Aéroport', 'adresse' => 'Terminal 1'],
        ];

        foreach ($initialSites as $siteData) {
            \App\Models\Site::updateOrCreate(
                ['nom' => $siteData['nom']],
                [
                    'adresse' => $siteData['adresse'],
                    'user_id' => $admin->id,
                ]
            );
        }

        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $site = \App\Models\Site::updateOrCreate(
            ['nom' => 'Parking Test'],
            [
                'adresse' => '1 rue de la Paix, Paris',
                'user_id' => $user->id,
            ]
        );

        // Créer une place liée à ce user
        \App\Models\Place::updateOrCreate(
            ['site_id' => $site->id, 'nom' => 'A-1'],
            [
                'user_id' => $user->id,
                'caracteristiques' => 'Place couverte, accès badge',
                'is_active' => true,
            ]
        );
    }
}
