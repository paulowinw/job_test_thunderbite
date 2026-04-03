<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameRevealedTile extends Model
{
    protected $table = 'game_revealed_tiles';

    protected $fillable = ['game_id', 'tile_index', 'prize_id'];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }
}

