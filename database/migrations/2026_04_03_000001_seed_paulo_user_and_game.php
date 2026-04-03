<?php

use App\Models\Campaign;
use App\Models\Game;
use App\Models\GameRevealedTile;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    private const CAMPAIGN_SLUG = 'test-campaign-1';

    private const PAULO_EMAIL = 'paulo@xtremepush.com';

    private const PAULO_ACCOUNT = 'paulo';

    public function up(): void
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

    public function down(): void
    {
        $campaign = Campaign::where('slug', self::CAMPAIGN_SLUG)->first();

        if ($campaign) {
            $games = Game::query()
                ->where('campaign_id', $campaign->id)
                ->where('account', self::PAULO_ACCOUNT)
                ->where('segment', 'low')
                ->whereNull('finished_at')
                ->whereNull('prize_id')
                ->get();

            foreach ($games as $game) {
                GameRevealedTile::where('game_id', $game->id)->delete();
                $game->delete();
            }
        }

        User::where('email', self::PAULO_EMAIL)->delete();
    }
};
