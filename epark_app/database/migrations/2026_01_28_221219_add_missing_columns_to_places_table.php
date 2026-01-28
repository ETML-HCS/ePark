<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->string('type')->nullable()->after('nom'); // voiture, moto, velo, etc.
            $table->json('dimensions_json')->nullable()->after('type'); // {"longueur": 4.5, "largeur": 2.0, "hauteur": 1.8}
            $table->json('equipments_json')->nullable()->after('dimensions_json'); // ["chargeur", "toit"]
            $table->integer('hourly_price_cents')->default(500)->after('equipments_json'); // prix en centimes
            $table->boolean('is_active')->default(true)->after('hourly_price_cents'); // remplacer disponible
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn(['type', 'dimensions_json', 'equipments_json', 'hourly_price_cents', 'is_active']);
        });
    }
};
