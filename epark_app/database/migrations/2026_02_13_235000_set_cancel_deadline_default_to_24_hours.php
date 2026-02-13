<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('places', 'cancel_deadline_hours')) {
            return;
        }

        DB::table('places')
            ->whereNull('cancel_deadline_hours')
            ->orWhere('cancel_deadline_hours', 12)
            ->update(['cancel_deadline_hours' => 24]);

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE places MODIFY cancel_deadline_hours TINYINT UNSIGNED NOT NULL DEFAULT 24');
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('places', 'cancel_deadline_hours')) {
            return;
        }

        DB::table('places')
            ->where('cancel_deadline_hours', 24)
            ->update(['cancel_deadline_hours' => 12]);

        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE places MODIFY cancel_deadline_hours TINYINT UNSIGNED NOT NULL DEFAULT 12');
        }
    }
};
