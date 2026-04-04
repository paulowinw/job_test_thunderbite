<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Game;
use App\Models\GameRevealedTile;
use App\Models\Prize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameRevealedTilesTest extends TestCase
{
    use RefreshDatabase;

    private function makeCampaignWithPrize(string $segment = 'low'): array
    {
        $campaign = Campaign::query()->create([
            'timezone' => 'UTC',
            'name' => 'Test Campaign',
            'slug' => 'test-campaign',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $prize = Prize::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Test Prize',
            'description' => null,
            'segment' => $segment,
            'weight' => 1,
            'image' => 'assets/1.png',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        return [$campaign, $prize];
    }

    public function test_loads_empty_revealed_tiles_for_new_account_on_first_visit(): void
    {
        [$campaign] = $this->makeCampaignWithPrize('low');

        $response = $this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'player-one',
            'segment' => 'low',
        ]));

        $response->assertOk();
        $response->assertViewHas('config', function (string $configJson) {
            $config = json_decode($configJson, true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('/api/flip', $config['apiPath']);
            $this->assertIsString($config['gameId']);
            $this->assertSame([], $config['revealedTiles']);

            return true;
        });
    }

    public function test_same_account_and_segment_reuses_same_game(): void
    {
        [$campaign] = $this->makeCampaignWithPrize('low');

        $first = $this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'player-one',
            'segment' => 'low',
        ]));
        $firstId = json_decode($first->viewData('config'), true, 512, JSON_THROW_ON_ERROR)['gameId'];

        $second = $this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'player-one',
            'segment' => 'low',
        ]));
        $secondId = json_decode($second->viewData('config'), true, 512, JSON_THROW_ON_ERROR)['gameId'];

        $this->assertSame($firstId, $secondId);
        $this->assertSame(1, Game::query()->where('account', 'player-one')->where('segment', 'low')->count());
    }

    public function test_embeds_stored_revealed_tiles_ordered_by_index(): void
    {
        [$campaign, $prize] = $this->makeCampaignWithPrize('low');

        $game = Game::query()->create([
            'campaign_id' => $campaign->id,
            'prize_id' => null,
            'account' => 'player-one',
            'segment' => 'low',
            'finished_at' => null,
        ]);

        GameRevealedTile::query()->create([
            'game_id' => $game->id,
            'tile_index' => 5,
            'prize_id' => $prize->id,
        ]);
        GameRevealedTile::query()->create([
            'game_id' => $game->id,
            'tile_index' => 1,
            'prize_id' => $prize->id,
        ]);

        $response = $this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'player-one',
            'segment' => 'low',
        ]));

        $response->assertOk();
        $response->assertViewHas('config', function (string $configJson) {
            $config = json_decode($configJson, true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame((string) Game::query()->first()->id, $config['gameId']);
            $this->assertSame([
                ['index' => 1, 'image' => 'assets/1.png'],
                ['index' => 5, 'image' => 'assets/1.png'],
            ], $config['revealedTiles']);

            return true;
        });
    }

    public function test_different_accounts_get_separate_games(): void
    {
        [$campaign] = $this->makeCampaignWithPrize('low');

        $a = json_decode($this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'alice',
            'segment' => 'low',
        ]))->viewData('config'), true, 512, JSON_THROW_ON_ERROR)['gameId'];

        $b = json_decode($this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'bob',
            'segment' => 'low',
        ]))->viewData('config'), true, 512, JSON_THROW_ON_ERROR)['gameId'];

        $this->assertNotSame($a, $b);
        $this->assertSame(2, Game::query()->count());
    }

    public function test_finished_game_is_skipped_and_new_game_is_started(): void
    {
        [$campaign] = $this->makeCampaignWithPrize('low');

        Game::query()->create([
            'campaign_id' => $campaign->id,
            'prize_id' => null,
            'account' => 'player-one',
            'segment' => 'low',
            'finished_at' => now(),
        ]);

        $response = $this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'player-one',
            'segment' => 'low',
        ]));

        $config = json_decode($response->viewData('config'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([], $config['revealedTiles']);

        $unfinished = Game::query()
            ->where('account', 'player-one')
            ->whereNull('finished_at')
            ->first();
        $this->assertNotNull($unfinished);
        $this->assertSame($config['gameId'], (string) $unfinished->id);
    }

    public function test_account_query_parameter_a_is_required(): void
    {
        [$campaign] = $this->makeCampaignWithPrize('low');

        $response = $this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'segment' => 'low',
        ]));

        $response->assertSessionHasErrors('a');
    }
}
