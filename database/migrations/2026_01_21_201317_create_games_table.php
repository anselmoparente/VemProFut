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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->date('game_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_players');
            $table->foreignId('status_id')->constrained('game_statuses');
            $table->foreignId('field_id')->constrained();
            $table->foreignId('organizer_id')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
