<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (! Schema::hasColumn('places', 'rental_rules_json')) {
                $table->json('rental_rules_json')->nullable()->after('hourly_price_cents');
            }
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (Schema::hasColumn('places', 'rental_rules_json')) {
                $table->dropColumn('rental_rules_json');
            }
        });
    }
};
