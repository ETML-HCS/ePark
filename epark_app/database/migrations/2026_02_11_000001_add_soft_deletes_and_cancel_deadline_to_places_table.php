<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (!Schema::hasColumn('places', 'cancel_deadline_hours')) {
                $table->unsignedTinyInteger('cancel_deadline_hours')->default(12)->after('is_active');
            }
            if (!Schema::hasColumn('places', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (Schema::hasColumn('places', 'cancel_deadline_hours')) {
                $table->dropColumn('cancel_deadline_hours');
            }
            if (Schema::hasColumn('places', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
