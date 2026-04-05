<?php

namespace App\Services;

use App\Exceptions\CampaignValidationException;
use App\Models\Campaign;
use App\Models\Game;
use App\Models\GameRevealedTile;

class GameLoader
{
    public function findOrCreateGame(Campaign $campaign, string $account, string $segment): Game
    {
        return Game::findOrCreateActiveForAccountAndSegment($campaign, $account, $segment);
    }

    public function revealedTiles(Game $game): array
    {
        return GameRevealedTile::orderedWithPrizeImagesForGame($game->id)
            ->map(fn ($tile) => [
                'index' => (int) $tile->tile_index,
                'image' => $tile->prize?->image,
            ])
            ->values()
            ->all();
    }

    public function validateCampaignPublicPlay(Campaign $campaign): void
    {
        $tz = $campaign->timezone ?? config('app.timezone');
        $now = now()->timezone($tz);

        if ($campaign->starts_at !== null && $now->lt($campaign->starts_at)) {
            throw new CampaignValidationException(__('The campaign has not started yet.'));
        }

        if ($campaign->ends_at !== null && $now->gt($campaign->ends_at)) {
            throw new CampaignValidationException(__('This campaign has ended.'));
        }
    }
}
