<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\GameRevealedTile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class GameRevealedTileSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        GameRevealedTile::truncate();
        Schema::enableForeignKeyConstraints();

        Game::query()
            ->whereNotNull('finished_at')
            ->whereNotNull('prize_id')
            ->orderBy('id')
            ->each(function (Game $game) {
                foreach ([0, 1, 2] as $tileIndex) {
                    GameRevealedTile::query()->create([
                        'game_id' => $game->id,
                        'tile_index' => $tileIndex,
                        'prize_id' => $game->prize_id,
                    ]);
                }
            });
    }
}
