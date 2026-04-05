<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Game;
use App\Models\GameRevealedTile;
use App\Models\Prize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * Segment filtering: players choose `segment` (`low`, `med`, `high`) when opening the campaign;
     * that value is stored on the game and restricts weighted picks to prizes in the same segment.
     *
     * @return array{campaign: Campaign, game: Game, prizes: array<string, Prize>}
     */
    private function makeCampaignWithLowMedHighPrizesAndGame(string $gameSegment): array
    {
        $campaign = Campaign::query()->create([
            'timezone' => 'UTC',
            'name' => 'Segment Pool Campaign',
            'slug' => 'segment-pool-campaign-'.$gameSegment,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $prizes = [];
        foreach (['low', 'med', 'high'] as $segment) {
            $prizes[$segment] = Prize::query()->create([
                'campaign_id' => $campaign->id,
                'name' => 'Prize '.$segment,
                'description' => null,
                'segment' => $segment,
                'weight' => $segment === $gameSegment ? 1 : 999,
                'image' => 'assets/flip-seg-'.$segment.'.png',
                'starts_at' => null,
                'ends_at' => null,
            ]);
        }

        $game = Game::query()->create([
            'campaign_id' => $campaign->id,
            'prize_id' => null,
            'account' => 'segment-pool-'.$gameSegment,
            'segment' => $gameSegment,
            'finished_at' => null,
        ]);

        return ['campaign' => $campaign, 'game' => $game, 'prizes' => $prizes];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function playerSegmentProvider(): array
    {
        return [
            'low' => ['low'],
            'med' => ['med'],
            'high' => ['high'],
        ];
    }

    #[DataProvider('playerSegmentProvider')]
    public function test_flip_only_draws_prizes_matching_game_segment(string $segment): void
    {
        ['game' => $game, 'prizes' => $prizes] = $this->makeCampaignWithLowMedHighPrizesAndGame($segment);

        $response = $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 3,
        ]);

        $response->assertOk();
        $this->assertSame($prizes[$segment]->image, $response->json('tileImage'));

        $this->assertDatabaseHas('game_revealed_tiles', [
            'game_id' => $game->id,
            'tile_index' => 3,
            'prize_id' => $prizes[$segment]->id,
        ]);
    }

    public function test_segment_query_parameter_on_campaign_load_sets_game_segment_for_flip_pool(): void
    {
        $campaign = Campaign::query()->create([
            'timezone' => 'UTC',
            'name' => 'Query Seg Campaign',
            'slug' => 'query-seg-campaign',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        foreach (['low', 'med', 'high'] as $seg) {
            Prize::query()->create([
                'campaign_id' => $campaign->id,
                'name' => 'Prize '.$seg,
                'description' => null,
                'segment' => $seg,
                'weight' => 1,
                'image' => 'assets/query-seg-'.$seg.'.png',
                'starts_at' => null,
                'ends_at' => null,
            ]);
        }

        $this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'query-seg-account',
            'segment' => 'med',
        ]))->assertOk();

        $game = Game::query()
            ->where('account', 'query-seg-account')
            ->where('segment', 'med')
            ->first();

        $this->assertNotNull($game);

        $medPrize = Prize::query()
            ->where('campaign_id', $campaign->id)
            ->where('segment', 'med')
            ->first();

        $response = $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => 8,
        ]);

        $response->assertOk();
        $this->assertSame($medPrize->image, $response->json('tileImage'));
        $this->assertDatabaseHas('game_revealed_tiles', [
            'game_id' => $game->id,
            'prize_id' => $medPrize->id,
        ]);
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

    /** 
     * Test that revealed tiles are persisted and embedded in the frontend after a campaign reload.
     * 
     * Test satisfies:
     * 6. **Game Persistence**: The game state must survive page refreshes.
     *  When a player returns, they should see their previously revealed tiles.
     * 
     * @return void
     * 
    */
    public function test_revealed_tile_stays_in_database_and_is_embedded_after_campaign_reload(): void
    {
        ['campaign' => $campaign, 'game' => $game, 'prize' => $prize] = $this->makeCampaignGameAndPrize();
        $tileIndex = 9;

        $this->postJson(route('api.flip'), [
            'gameId' => $game->id,
            'tileIndex' => $tileIndex,
        ])->assertOk();

        $this->assertDatabaseHas('game_revealed_tiles', [
            'game_id' => $game->id,
            'tile_index' => $tileIndex,
            'prize_id' => $prize->id,
        ]);

        $reload = $this->get(route('campaign.show', [
            'campaign' => $campaign->slug,
            'a' => 'flip-tester',
            'segment' => 'low',
        ]));

        $reload->assertOk();
        $config = json_decode($reload->viewData('config'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame((string) $game->id, $config['gameId']);
        $this->assertSame([
            ['index' => $tileIndex, 'image' => $prize->image],
        ], $config['revealedTiles']);
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
