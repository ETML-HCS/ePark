<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (!Schema::hasColumn('places', 'visual_day_start_time')) {
                $table->time('visual_day_start_time')->nullable()->after('weekly_schedule_type');
            }

            if (!Schema::hasColumn('places', 'visual_day_end_time')) {
                $table->time('visual_day_end_time')->nullable()->after('visual_day_start_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $drops = [];

            foreach (['visual_day_start_time', 'visual_day_end_time'] as $column) {
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
