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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'express_token')) {
                $table->string('express_token', 64)->nullable()->unique()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'auto_pay_enabled')) {
                $table->boolean('auto_pay_enabled')->default(true)->after('express_token');
            }
            if (! Schema::hasColumn('users', 'quick_vehicle_plate')) {
                $table->string('quick_vehicle_plate', 30)->nullable()->after('auto_pay_enabled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'quick_vehicle_plate')) {
                $table->dropColumn('quick_vehicle_plate');
            }
            if (Schema::hasColumn('users', 'auto_pay_enabled')) {
                $table->dropColumn('auto_pay_enabled');
            }
            if (Schema::hasColumn('users', 'express_token')) {
                $table->dropColumn('express_token');
            }
        });
    }
};
