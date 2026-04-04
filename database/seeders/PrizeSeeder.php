<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Prize;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PrizeSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Prize::truncate();
        Schema::enableForeignKeyConstraints();

        $campaigns = Campaign::all();

        foreach ($campaigns as $campaign) {
            Prize::insert([
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'Low 1',
                    'segment' => 'low',
                    'weight' => '25.00',
                    'daily_wins_limit' => 1,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/1.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'Low 2',
                    'segment' => 'low',
                    'weight' => '25.00',
                    'daily_wins_limit' => 10,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/2.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'Low 3',
                    'segment' => 'low',
                    'weight' => '50.00',
                    'daily_wins_limit' => 10,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/3.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'Med 1',
                    'segment' => 'med',
                    'weight' => '25.00',
                    'daily_wins_limit' => 10,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/4.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'Med 2',
                    'segment' => 'med',
                    'weight' => '25.00',
                    'daily_wins_limit' => 10,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/5.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'Med 3',
                    'segment' => 'med',
                    'weight' => '50.00',
                    'daily_wins_limit' => 10,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/6.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'High 1',
                    'segment' => 'high',
                    'weight' => '25.00',
                    'daily_wins_limit' => 10,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/7.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'High 2',
                    'segment' => 'high',
                    'weight' => '25.00',
                    'daily_wins_limit' => 10,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/1.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
                [
                    'campaign_id' => $campaign->id,
                    'name' => 'High 3',
                    'segment' => 'high',
                    'weight' => '50.00',
                    'daily_wins_limit' => 10,
                    'daily_wins_count' => 0,
                    'daily_wins_count_date' => now()->toDateString(),
                    'image' => '/assets/2.png',
                    'starts_at' => now()->subDays(10)->startOfDay(),
                    'ends_at' => now()->addDays(7)->endOfDay(),
                ],
            ]);
        }
    }
}
