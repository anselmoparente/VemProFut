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
        Schema::create('operating_hours', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('day_of_week');
            $table->time('open_time');
            $table->time('close_time');
            $table->foreignId('sports_center_id')->constrained();
            $table->timestamps();

            $table->unique(['sports_center_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operating_hours');
    }
};
