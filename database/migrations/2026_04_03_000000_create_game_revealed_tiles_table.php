<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_revealed_tiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tile_index');
            $table->foreignId('prize_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['game_id', 'tile_index']);
            $table->index(['game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_revealed_tiles');
    }
};
