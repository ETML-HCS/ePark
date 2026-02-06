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
        Schema::table('reservations', function (Blueprint $table) {
            $table->dateTime('actual_end_at')->nullable()->after('date_fin');
            $table->integer('overstay_minutes')->default(0)->after('actual_end_at');
            $table->integer('penalty_cents')->default(0)->after('overstay_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['actual_end_at', 'overstay_minutes', 'penalty_cents']);
        });
    }
};
