<?php

namespace App\Services;

use App\Exceptions\CampaignValidationException;
use App\Models\Campaign;
use App\Models\Game;

class GameLoader
{
    public function findOrCreateGame(Campaign $campaign, string $account, string $segment): Game
    {
        $game = Game::query()
            ->where('campaign_id', $campaign->id)
            ->where('account', $account)
            ->where('segment', $segment)
            ->whereNull('finished_at')
            ->latest('id')
            ->first();

        if (! $game) {
            $game = Game::create([
                'campaign_id' => $campaign->id,
                'account' => $account,
                'segment' => $segment,
            ]);
        }

        return $game;
    }

    public function revealedTiles(Game $game): array
    {
        return $game->revealedTiles()
            ->with(['prize:id,image'])
            ->orderBy('tile_index')
            ->get()
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
