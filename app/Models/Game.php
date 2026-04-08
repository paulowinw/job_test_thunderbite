<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    use HasFactory;

    protected $fillable = ['campaign_id', 'prize_id', 'account', 'segment', 'finished_at'];

    protected function casts(): array
    {
        return [
            'finished_at' => 'datetime',
        ];
    }

    public static function filter(
        ?string $account = null,
        ?int $prizeId = null,
        ?string $fromDate = null,
        ?string $tillDate = null,
    ): Builder {
        $query = self::query();
        $campaign = Campaign::find(session('activeCampaign'));

        // When filtering by dates, keep in mind `finished_at` should be stored in Campaign timezone

        return $query;
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function revealedTiles(): HasMany
    {
        return $this->hasMany(GameRevealedTile::class);
    }

    public static function findForRevealWithLock(int $gameId): ?self
    {
        return static::query()
            ->whereKey($gameId)
            ->lockForUpdate()
            ->with('campaign')
            ->first();
    }

    public static function findOrCreateActiveForAccountAndSegment(Campaign $campaign, string $account, string $segment): self
    {
        $game = static::query()
            ->where('campaign_id', $campaign->id)
            ->where('account', $account)
            ->where('segment', $segment)
            ->whereNull('finished_at')
            ->latest('id')
            ->first();

        if ($game !== null) {
            return $game;
        }

        return static::create([
            'campaign_id' => $campaign->id,
            'account' => $account,
            'segment' => $segment,
        ]);
    }
}
