<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (!Schema::hasColumn('places', 'pending_validation_default')) {
                $table->string('pending_validation_default', 20)
                    ->default('manual')
                    ->after('cancel_deadline_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (Schema::hasColumn('places', 'pending_validation_default')) {
                $table->dropColumn('pending_validation_default');
            }
        });
    }
};
