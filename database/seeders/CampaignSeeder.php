<?php

namespace Database\Seeders;

use App\Models\Campaign;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Campaign::truncate();
        Schema::enableForeignKeyConstraints();
        Campaign::create([
            'timezone' => 'Europe/London',
            'name' => 'Test Campaign 1',
            'slug' => 'test-campaign-1',
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->addDays(7)->endOfDay(),
        ]);

        Campaign::create([
            'timezone' => 'Europe/London',
            'name' => 'Test Campaign 2',
            'slug' => 'test-campaign-2',
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->addDays(7)->endOfDay(),
        ]);

        Campaign::create([
            'timezone' => 'Europe/London',
            'name' => 'Test Campaign 3',
            'slug' => 'test-campaign-3',
            'starts_at' => now()->addDay()->startOfDay(),
            'ends_at' => now()->addDays(8)->endOfDay(),
        ]);

        Campaign::create([
            'timezone' => 'Europe/London',
            'name' => 'Test Campaign 4',
            'slug' => 'test-campaign-4',
            'starts_at' => now()->subDays(8)->startOfDay(),
            'ends_at' => now()->subDay()->endOfDay(),
        ]);
    }
}
