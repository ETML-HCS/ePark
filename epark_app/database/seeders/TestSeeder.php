<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $uid = DB::table('users')->insertGetId([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'proprietaire',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $sid = DB::table('sites')->insertGetId([
            'nom' => 'Parking Central',
            'adresse' => '10 Rue Principale',
            'user_id' => $uid,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('places')->insert([
            [
                'site_id' => $sid,
                'nom' => 'A-1',
                'caracteristiques' => 'Couvert, proche entrée',
                'user_id' => $uid,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'site_id' => $sid,
                'nom' => 'A-2',
                'caracteristiques' => 'Extérieur, ombragé',
                'user_id' => $uid,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
