<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('reservations', 'owner_deadline_reminder_sent_at')) {
                $table->timestamp('owner_deadline_reminder_sent_at')
                    ->nullable()
                    ->after('end_reminder_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (Schema::hasColumn('reservations', 'owner_deadline_reminder_sent_at')) {
                $table->dropColumn('owner_deadline_reminder_sent_at');
            }
        });
    }
};
