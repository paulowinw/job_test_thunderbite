<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameRevealedTile;

class GameRevealedTilesPayload
{
    public function forGame(Game $game): array
    {
        return GameRevealedTile::orderedWithPrizeImagesForGame($game->id)
            ->map(fn ($tile) => [
                'index' => (int) $tile->tile_index,
                'image' => $tile->prize?->image,
            ])
            ->values()
            ->all();
    }
}
