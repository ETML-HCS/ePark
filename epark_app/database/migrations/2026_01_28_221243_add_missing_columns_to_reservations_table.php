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
            $table->integer('amount_cents')->default(0)->after('battement_minutes'); // montant en centimes
            $table->string('payment_status')->default('pending')->after('amount_cents'); // pending, paid, failed, refunded
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['amount_cents', 'payment_status']);
        });
    }
};
