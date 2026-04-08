<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prize extends Model
{
    protected $fillable = [
        'campaign_id',
        'name',
        'description',
        'segment',
        'weight',
        'daily_wins_limit',
        'daily_wins_count',
        'daily_wins_count_date',
        'image',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'daily_wins_count_date' => 'date',
        ];
    }

    public static function search(?string $query = null): Builder
    {
        return empty($query) ? static::query()
            : static::where('name', 'like', '%'.$query.'%');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Prizes eligible for a weighted draw: same campaign and segment, positive weight,
     * and within starts_at / ends_at evaluated in the campaign timezone.
     * Daily win limits are enforced in {@see \App\Services\RevealTileHandler::reveal()}.
     */
    public function scopeForWeightedPick(Builder $query, Game $game): void
    {
        $game->loadMissing('campaign');
        $campaign = $game->campaign;

        if ($campaign === null) {
            throw new \InvalidArgumentException('Game must belong to a campaign.');
        }

        $now = now()->timezone($campaign->timezone);

        $query->where('campaign_id', $game->campaign_id)
            ->where('segment', $game->segment)
            ->whereNotNull('weight')
            ->where('weight', '>', 0)
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            });
    }

    public static function findWithLockForUpdate(int $id): ?self
    {
        return static::query()->whereKey($id)->lockForUpdate()->first();
    }
}
