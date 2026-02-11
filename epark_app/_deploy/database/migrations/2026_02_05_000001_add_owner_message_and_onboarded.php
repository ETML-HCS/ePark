<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter le champ message de confirmation/refus sur les réservations
        Schema::table('reservations', function (Blueprint $table) {
            $table->text('owner_message')->nullable()->after('payment_status');
        });

        // Ajouter le champ onboarded sur les utilisateurs pour tracker l'onboarding
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('onboarded')->default(false)->after('favorite_site_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('owner_message');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarded');
        });
    }
};
