<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (!Schema::hasColumn('places', 'weekly_schedule_type')) {
                $table->string('weekly_schedule_type', 20)->nullable()->after('availability_end_date');
            }

            if (!Schema::hasColumn('places', 'is_group_reserved')) {
                $table->boolean('is_group_reserved')->default(false)->after('weekly_schedule_type');
            }

            if (!Schema::hasColumn('places', 'group_name')) {
                $table->string('group_name', 120)->nullable()->after('is_group_reserved');
            }

            if (!Schema::hasColumn('places', 'group_access_code_hash')) {
                $table->string('group_access_code_hash')->nullable()->after('group_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $drops = [];

            foreach (['weekly_schedule_type', 'is_group_reserved', 'group_name', 'group_access_code_hash'] as $column) {
                if (Schema::hasColumn('places', $column)) {
                    $drops[] = $column;
                }
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
