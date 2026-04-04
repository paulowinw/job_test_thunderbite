<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Game;
use App\Models\GameRevealedTile;
use App\Models\Prize;
use Illuminate\Database\Seeder;

class PauloRevealedTilesSeeder extends Seeder
{
    private const CAMPAIGN_SLUG = 'test-campaign-1';

    private const PAULO_ACCOUNT = 'paulo';

    public function run(): void
    {
        $campaign = Campaign::where('slug', self::CAMPAIGN_SLUG)->first();

        if (! $campaign) {
            return;
        }

        $game = Game::query()
            ->where('campaign_id', $campaign->id)
            ->where('account', self::PAULO_ACCOUNT)
            ->where('segment', 'low')
            ->whereNull('finished_at')
            ->whereNull('prize_id')
            ->first();

        $lowPrizeQuery = Prize::query()
            ->where('campaign_id', $campaign->id)
            ->where('segment', 'low')
            ->orderBy('id');

        $lowPrizeOne = $lowPrizeQuery->first();
        $lowPrizeTwo = $lowPrizeQuery->skip(1)->first();

        if (! $game || ! $lowPrizeOne) {
            return;
        }

        foreach ([0, 1] as $tileIndex) {
            GameRevealedTile::firstOrCreate(
                [
                    'game_id' => $game->id,
                    'tile_index' => $tileIndex,
                ],
                [
                    'prize_id' => $lowPrizeOne->id,
                ]
            );
        }

        if ($lowPrizeTwo) {
            GameRevealedTile::firstOrCreate(
                [
                    'game_id' => $game->id,
                    'tile_index' => 2,
                ],
                [
                    'prize_id' => $lowPrizeTwo->id,
                ]
            );
        }
    }
}
