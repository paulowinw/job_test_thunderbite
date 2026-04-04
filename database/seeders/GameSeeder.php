<?php

namespace Database\Seeders;

use App\Models\Game;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class GameSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Game::truncate();
        Schema::enableForeignKeyConstraints();
        Game::factory()->count(100)->create();
    }
}
