<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Game;
use App\Models\Prize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GameLoadTest extends TestCase
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

    public function test_shows_unavailable_view_when_campaign_not_yet_started_and_does_not_create_game(): void
    {
        $this->travelTo(Carbon::parse('2026-05-15 12:00:00', 'UTC'));
        try {
            $campaign = Campaign::query()->create([
                'timezone' => 'UTC',
                'name' => 'Future Start Campaign',
                'slug' => 'future-start-campaign',
                'starts_at' => Carbon::parse('2026-06-01 00:00:00', 'UTC'),
                'ends_at' => Carbon::parse('2026-06-30 23:59:59', 'UTC'),
            ]);

            Prize::query()->create([
                'campaign_id' => $campaign->id,
                'name' => 'Prize',
                'description' => null,
                'segment' => 'low',
                'weight' => 1,
                'image' => 'assets/1.png',
                'starts_at' => null,
                'ends_at' => null,
            ]);

            $this->assertSame(0, Game::query()->count());

            $response = $this->get(route('campaign.show', [
                'campaign' => $campaign->slug,
                'a' => 'player-one',
                'segment' => 'low',
            ]));

            $response->assertOk();
            $response->assertViewIs('frontend.campaign-unavailable');
            $response->assertViewHas('message', __('The campaign has not started yet.'));
            $response->assertSee(__('The campaign has not started yet.'), false);
            $this->assertSame(0, Game::query()->count());
        } finally {
            $this->travelBack();
        }
    }

    public function test_shows_unavailable_view_when_campaign_ended_and_does_not_create_game(): void
    {
        $this->travelTo(Carbon::parse('2026-07-05 12:00:00', 'UTC'));
        try {
            $campaign = Campaign::query()->create([
                'timezone' => 'UTC',
                'name' => 'Past End Campaign',
                'slug' => 'past-end-campaign',
                'starts_at' => Carbon::parse('2026-06-01 00:00:00', 'UTC'),
                'ends_at' => Carbon::parse('2026-06-30 23:59:59', 'UTC'),
            ]);

            Prize::query()->create([
                'campaign_id' => $campaign->id,
                'name' => 'Prize',
                'description' => null,
                'segment' => 'low',
                'weight' => 1,
                'image' => 'assets/1.png',
                'starts_at' => null,
                'ends_at' => null,
            ]);

            $this->assertSame(0, Game::query()->count());

            $response = $this->get(route('campaign.show', [
                'campaign' => $campaign->slug,
                'a' => 'player-one',
                'segment' => 'low',
            ]));

            $response->assertOk();
            $response->assertViewIs('frontend.campaign-unavailable');
            $response->assertViewHas('message', __('This campaign has ended.'));
            $response->assertSee(__('This campaign has ended.'), false);
            $this->assertSame(0, Game::query()->count());
        } finally {
            $this->travelBack();
        }
    }

    public function test_campaign_with_null_schedule_loads_index_at_fixed_now(): void
    {
        $this->travelTo(Carbon::parse('2026-03-01 12:00:00', 'UTC'));
        try {
            [$campaign] = $this->makeCampaignWithPrize('low');

            $response = $this->get(route('campaign.show', [
                'campaign' => $campaign->slug,
                'a' => 'player-one',
                'segment' => 'low',
            ]));

            $response->assertOk();
            $response->assertViewIs('frontend.index');
            $response->assertViewHas('config', function (string $configJson) {
                $config = json_decode($configJson, true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame('/api/flip', $config['apiPath']);
                $this->assertIsString($config['gameId']);

                return true;
            });
        } finally {
            $this->travelBack();
        }
    }
}
