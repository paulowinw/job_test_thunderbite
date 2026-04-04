<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CampaignSeeder::class,
            PrizeSeeder::class,
            GameSeeder::class,
            PauloUserAndGameSeeder::class,
            GameRevealedTileSeeder::class,
            PauloRevealedTilesSeeder::class,
        ]);
    }
}
