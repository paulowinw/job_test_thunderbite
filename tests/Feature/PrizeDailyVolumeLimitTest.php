<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Game;
use App\Models\GameRevealedTile;
use App\Models\Prize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrizeDailyVolumeLimitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A prize with daily_wins_limit = 1 starts at daily_wins_count = 0. The first completed
     * win increments the counter. A second game that would complete a win for the same prize
     * is blocked on the third matching reveal with an error response.
     */
    public function test_second_completed_win_is_rejected_when_daily_limit_reached(): void
    {
        $campaign = Campaign::query()->create([
            'timezone' => 'UTC',
            'name' => 'Daily Cap Campaign',
            'slug' => 'daily-cap-campaign',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $limitedPrize = Prize::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Limited',
            'description' => null,
            'segment' => 'low',
            'weight' => 1,
            'daily_wins_limit' => 1,
            'daily_wins_count' => 0,
            'image' => 'assets/limited.png',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $gameOne = Game::query()->create([
            'campaign_id' => $campaign->id,
            'prize_id' => null,
            'account' => 'player-one',
            'segment' => 'low',
            'finished_at' => null,
        ]);

        $this->postJson(route('api.flip'), [
            'gameId' => $gameOne->id,
            'tileIndex' => 0,
        ])->assertOk()->assertJsonMissingPath('message');

        $this->postJson(route('api.flip'), [
            'gameId' => $gameOne->id,
            'tileIndex' => 1,
        ])->assertOk()->assertJsonMissingPath('message');

        $firstWin = $this->postJson(route('api.flip'), [
            'gameId' => $gameOne->id,
            'tileIndex' => 2,
        ]);

        $firstWin->assertOk();
        $firstWin->assertJson([
            'tileImage' => $limitedPrize->image,
            'message' => 'You won a prize!',
        ]);

        $gameOne->refresh();
        $this->assertSame($limitedPrize->id, $gameOne->prize_id);
        $this->assertNotNull($gameOne->finished_at);

        $limitedPrize->refresh();
        $this->assertSame(1, (int) $limitedPrize->daily_wins_count);

        $gameTwo = Game::query()->create([
            'campaign_id' => $campaign->id,
            'prize_id' => null,
            'account' => 'player-two',
            'segment' => 'low',
            'finished_at' => null,
        ]);

        $this->postJson(route('api.flip'), [
            'gameId' => $gameTwo->id,
            'tileIndex' => 0,
        ])->assertOk()->assertJsonMissingPath('message');

        $this->postJson(route('api.flip'), [
            'gameId' => $gameTwo->id,
            'tileIndex' => 1,
        ])->assertOk()->assertJsonMissingPath('message');

        $blocked = $this->postJson(route('api.flip'), [
            'gameId' => $gameTwo->id,
            'tileIndex' => 2,
        ]);

        $blocked->assertOk();
        $blocked->assertJson([
            'message' => 'The daily limit for this prize was reached.',
        ]);

        $gameTwo->refresh();
        $this->assertNull($gameTwo->prize_id);
        $this->assertNull($gameTwo->finished_at);

        $this->assertSame(2, GameRevealedTile::query()->where('game_id', $gameTwo->id)->count());

        $limitedPrize->refresh();
        $this->assertSame(1, (int) $limitedPrize->daily_wins_count);
    }
}
