<?php

namespace App\Contracts;

use App\Models\Game;
use App\Models\Prize;

interface PrizePicker
{
    public function pick(Game $game): ?Prize;
}
