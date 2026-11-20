<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'twint_phone')) {
                $table->string('twint_phone', 32)->nullable()->after('quick_vehicle_plate');
            }
            if (! Schema::hasColumn('users', 'iban')) {
                $table->string('iban', 42)->nullable()->after('twint_phone');
            }
            if (! Schema::hasColumn('users', 'payment_instructions')) {
                $table->string('payment_instructions', 500)->nullable()->after('iban');
            }
        });

        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'vehicle_plate')) {
                $table->string('vehicle_plate', 30)->nullable()->after('battement_minutes');
            }
            if (! Schema::hasColumn('reservations', 'payment_received_at')) {
                $table->timestamp('payment_received_at')->nullable()->after('paiement_effectue');
            }
        });

        Schema::table('sites', function (Blueprint $table) {
            if (! Schema::hasColumn('sites', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('adresse');
            }
            if (! Schema::hasColumn('sites', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['twint_phone', 'iban', 'payment_instructions'],
                fn (string $column) => Schema::hasColumn('users', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('reservations', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['vehicle_plate', 'payment_received_at'],
                fn (string $column) => Schema::hasColumn('reservations', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('sites', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['latitude', 'longitude'],
                fn (string $column) => Schema::hasColumn('sites', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
