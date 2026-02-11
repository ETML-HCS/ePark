<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 0 (dim) -> 6 (sam)
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['place_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_availabilities');
    }
};
