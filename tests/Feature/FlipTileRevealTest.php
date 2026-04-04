<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Game;
use App\Models\GameRevealedTile;
use App\Models\Prize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FlipTileRevealTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{campaign: Campaign, game: Game, prize: Prize}
     */
    private function makeCampaignGameAndPrize(string $segment = 'low', string $image = 'assets/prize-a.png'): array
    {
        $campaign = Campaign::query()->create([
            'timezone' => 'UTC',
            'name' => 'Flip Test Campaign',
            'slug' => 'flip-test-campaign',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $prize = Prize::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Eligible Prize',
            'description' => null,
            'segment' => $segment,
            'weight' => 1,
            'image' => $image,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $game = Game::query()->create([
            'campaign_id' => $campaign->id,
            'prize_id' => null,
            'account' => 'flip-tester',
            'segment' => $segment,
            'finished_at' => null,
        ]);

        return ['campaign' => $campaign, 'game' => $game, 'prize' => $prize];
    }

    public function test_reveal_new_tile_persists_row_and_returns_prize_image(): void
    {
        ['game' => $game, 'prize' => $prize] = $this->makeCampaignGameAndPrize();

        $response = $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 7,
        ]);

        $response->assertOk();
        $response->assertJson([
            'tileImage' => $prize->image,
        ]);
        $response->assertJsonMissingPath('message');

        $this->assertDatabaseHas('game_revealed_tiles', [
            'game_id' => $game->id,
            'tile_index' => 7,
            'prize_id' => $prize->id,
        ]);
    }

    public function test_same_tile_index_is_idempotent_and_does_not_insert_second_row(): void
    {
        ['game' => $game, 'prize' => $prize] = $this->makeCampaignGameAndPrize();

        $first = $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 4,
        ]);
        $first->assertOk();

        $second = $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 4,
        ]);
        $second->assertOk();

        $this->assertSame($first->json('tileImage'), $second->json('tileImage'));
        $this->assertSame($prize->image, $second->json('tileImage'));
        $this->assertSame(1, GameRevealedTile::query()->where('game_id', $game->id)->count());
    }

    public function test_third_matching_reveal_finishes_game_and_returns_win_message(): void
    {
        ['game' => $game, 'prize' => $prize] = $this->makeCampaignGameAndPrize();

        $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 0,
        ])->assertOk()->assertJsonMissingPath('message');

        $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 1,
        ])->assertOk()->assertJsonMissingPath('message');

        $response = $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 2,
        ]);

        $response->assertOk();
        $response->assertJson([
            'tileImage' => $prize->image,
            'message' => 'You won a prize!',
        ]);

        $game->refresh();
        $this->assertNotNull($game->finished_at);
        $this->assertSame($prize->id, $game->prize_id);
    }

    public function test_only_prizes_matching_game_segment_are_eligible(): void
    {
        $campaign = Campaign::query()->create([
            'timezone' => 'UTC',
            'name' => 'Segment Campaign',
            'slug' => 'segment-campaign',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $prizeLow = Prize::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Low seg',
            'description' => null,
            'segment' => 'low',
            'weight' => 1,
            'image' => 'assets/only-low.png',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        Prize::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'High seg',
            'description' => null,
            'segment' => 'high',
            'weight' => 999,
            'image' => 'assets/only-high.png',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $game = Game::query()->create([
            'campaign_id' => $campaign->id,
            'prize_id' => null,
            'account' => 'seg-player',
            'segment' => 'low',
            'finished_at' => null,
        ]);

        $response = $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 10,
        ]);

        $response->assertOk();
        $this->assertSame($prizeLow->image, $response->json('tileImage'));

        $this->assertDatabaseHas('game_revealed_tiles', [
            'game_id' => $game->id,
            'prize_id' => $prizeLow->id,
        ]);
    }

    public function test_sqlite_weighted_pick_does_not_use_rand_in_sql(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        ['game' => $game] = $this->makeCampaignGameAndPrize();

        $sqlLog = [];
        DB::listen(function ($query) use (&$sqlLog): void {
            $sqlLog[] = $query->sql;
        });

        $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 12,
        ])->assertOk();

        foreach ($sqlLog as $sql) {
            $this->assertDoesNotMatchRegularExpression('/\bRAND\s*\(/i', $sql);
        }
    }
}
