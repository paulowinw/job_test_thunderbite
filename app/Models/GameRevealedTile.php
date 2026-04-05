<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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

    public static function findByGameAndTileIndexWithPrize(int $gameId, int $tileIndex): ?self
    {
        return static::query()
            ->where('game_id', $gameId)
            ->where('tile_index', $tileIndex)
            ->with('prize:id,image')
            ->first();
    }

    public static function createForGameTile(int $gameId, int $tileIndex, int $prizeId): self
    {
        return static::query()->create([
            'game_id' => $gameId,
            'tile_index' => $tileIndex,
            'prize_id' => $prizeId,
        ]);
    }

    public static function countRevealsForGameAndPrize(int $gameId, int $prizeId): int
    {
        return static::query()
            ->where('game_id', $gameId)
            ->where('prize_id', $prizeId)
            ->count();
    }

    public static function findPrizeIdWithThreeOrMoreReveals(int $gameId): ?int
    {
        $row = static::query()
            ->where('game_id', $gameId)
            ->selectRaw('prize_id')
            ->groupBy('prize_id')
            ->havingRaw('COUNT(*) >= ?', [3])
            ->first();

        return $row !== null ? (int) $row->prize_id : null;
    }

    /**
     * @return Collection<int, self>
     */
    public static function orderedWithPrizeImagesForGame(int $gameId): Collection
    {
        return static::query()
            ->where('game_id', $gameId)
            ->with(['prize:id,image'])
            ->orderBy('tile_index')
            ->get();
    }
}

