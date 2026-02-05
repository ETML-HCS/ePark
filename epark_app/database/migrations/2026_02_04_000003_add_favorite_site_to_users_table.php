<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'favorite_site_id')) {
                $table->foreignId('favorite_site_id')->nullable()->constrained('sites')->nullOnDelete()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'favorite_site_id')) {
                $table->dropForeign(['favorite_site_id']);
                $table->dropColumn('favorite_site_id');
            }
        });
    }
};
