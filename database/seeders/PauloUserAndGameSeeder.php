<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Game;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PauloUserAndGameSeeder extends Seeder
{
    private const CAMPAIGN_SLUG = 'test-campaign-1';

    private const PAULO_EMAIL = 'paulo@xtremepush.com';

    private const PAULO_ACCOUNT = 'paulo';

    public function run(): void
    {
        $campaign = Campaign::firstOrCreate(
            ['slug' => self::CAMPAIGN_SLUG],
            [
                'timezone' => 'Europe/London',
                'name' => 'Test Campaign 1',
                'starts_at' => now()->startOfDay(),
                'ends_at' => now()->addDays(7)->endOfDay(),
            ]
        );

        Prize::firstOrCreate(
            [
                'campaign_id' => $campaign->id,
                'name' => 'Low 1',
            ],
            [
                'segment' => 'low',
                'weight' => '25.00',
                'image' => '/assets/1.png',
                'starts_at' => now()->subDays(10)->startOfDay(),
                'ends_at' => now()->addDays(7)->endOfDay(),
            ]
        );

        User::firstOrCreate(
            ['email' => self::PAULO_EMAIL],
            [
                'name' => 'Paulo',
                'level' => 'readonly',
                'password' => Hash::make(env('PAULO_SEED_PASSWORD', 'paulo123')),
            ]
        );

        $exists = Game::query()
            ->where('campaign_id', $campaign->id)
            ->where('account', self::PAULO_ACCOUNT)
            ->where('segment', 'low')
            ->whereNull('finished_at')
            ->whereNull('prize_id')
            ->exists();

        if (! $exists) {
            Game::create([
                'campaign_id' => $campaign->id,
                'prize_id' => null,
                'account' => self::PAULO_ACCOUNT,
                'segment' => 'low',
                'finished_at' => null,
            ]);
        }
    }
}
