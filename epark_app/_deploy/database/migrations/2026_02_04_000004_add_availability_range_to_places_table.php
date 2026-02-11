<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (!Schema::hasColumn('places', 'availability_start_date')) {
                $table->date('availability_start_date')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('places', 'availability_end_date')) {
                $table->date('availability_end_date')->nullable()->after('availability_start_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (Schema::hasColumn('places', 'availability_start_date')) {
                $table->dropColumn('availability_start_date');
            }
            if (Schema::hasColumn('places', 'availability_end_date')) {
                $table->dropColumn('availability_end_date');
            }
        });
    }
};
