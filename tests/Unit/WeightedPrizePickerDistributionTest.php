<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Game;
use App\Models\Prize;
use App\Services\WeightedPrizePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeightedPrizePickerDistributionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The PHP fallback matches -LOG(RAND())/weight ASC on MySQL (min of independent Exp(weight)); empirical share for weight 1 vs 3 should cluster near 25%.
     */
    public function test_gumbel_max_selection_matches_relative_weights(): void
    {
        $campaign = Campaign::query()->create([
            'timezone' => 'UTC',
            'name' => 'Picker stats',
            'slug' => 'picker-stats',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $light = Prize::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Light',
            'description' => null,
            'segment' => 'low',
            'weight' => 1,
            'image' => 'a.png',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        Prize::query()->create([
            'campaign_id' => $campaign->id,
            'name' => 'Heavy',
            'description' => null,
            'segment' => 'low',
            'weight' => 3,
            'image' => 'b.png',
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $game = Game::query()->create([
            'campaign_id' => $campaign->id,
            'prize_id' => null,
            'account' => 'stat-test',
            'segment' => 'low',
            'finished_at' => null,
        ]);

        $picker = new WeightedPrizePicker;
        $method = new \ReflectionMethod(WeightedPrizePicker::class, 'pickUsingGumbelMax');
        $method->setAccessible(true);

        $query = Prize::query()->forWeightedPick($game);

        $lightWins = 0;
        $iterations = 15_000;

        for ($i = 0; $i < $iterations; $i++) {
            $chosen = $method->invoke($picker, $query->clone());
            if ($chosen->id === $light->id) {
                $lightWins++;
            }
        }

        $ratio = $lightWins / $iterations;
        $this->assertGreaterThan(0.22, $ratio, 'Expected ~25% wins for weight-1 prize vs weight-3.');
        $this->assertLessThan(0.28, $ratio, 'Expected ~25% wins for weight-1 prize vs weight-3.');
    }
}
