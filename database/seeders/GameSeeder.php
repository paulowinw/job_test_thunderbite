<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameRevealedTile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        GameRevealedTile::truncate();
        Game::truncate();
        Schema::enableForeignKeyConstraints();
        Game::factory()->count(100)->create();
    }
}
