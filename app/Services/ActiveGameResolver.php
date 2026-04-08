<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Game;

class ActiveGameResolver
{
    public function resolveForAccountAndSegment(Campaign $campaign, string $account, string $segment): Game
    {
        return Game::findOrCreateActiveForAccountAndSegment($campaign, $account, $segment);
    }
}
