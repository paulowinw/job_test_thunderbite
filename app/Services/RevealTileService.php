<?php

namespace App\Services;

use App\Exceptions\GameValidationException;
use App\Models\Game;
use App\Models\GameRevealedTile;
use App\Models\Prize;
use Illuminate\Support\Facades\DB;

class RevealTileService
{
    private const FALLBACK_TILE_IMAGE = '/assets/1.png';

    public function __construct(
        private readonly WeightedPrizePicker $picker,
    ) {}

    /**
     * @return array{tileImage: string, message?: string}
     */
    public function reveal(int $gameId, int $tileIndex): array
    {
        return DB::transaction(function () use ($gameId, $tileIndex) {
            /** @var Game|null $game */
            $game = Game::query()
                ->whereKey($gameId)
                ->lockForUpdate()
                ->with('campaign')
                ->first();

            $game = $this->validateGameCanProceed($game);

            $existingTile = GameRevealedTile::query()
                ->where('game_id', $gameId)
                ->where('tile_index', $tileIndex)
                ->with('prize:id,image')
                ->first();

            if ($existingTile !== null) {
                return [
                    'tileImage' => $this->tileImageFromPrize($existingTile->prize?->image),
                ];
            }

            $prize = $this->validatePrizeAvailableForDraw($this->picker->pick($game));

            $this->validateDailyWinLimitAllowsCompletion($game, $prize);

            GameRevealedTile::query()->create([
                'game_id' => $game->id,
                'tile_index' => $tileIndex,
                'prize_id' => $prize->id,
            ]);

            $winningPrizeId = $this->findWinningPrizeId($game->id);

            $payload = [
                'tileImage' => $this->tileImageFromPrize($prize->image),
            ];

            if ($winningPrizeId !== null) {
                $awarded = Prize::query()->whereKey($winningPrizeId)->lockForUpdate()->first();
                if ($awarded !== null && $awarded->daily_wins_limit !== null) {
                    $today = $this->todayStringForGame($game);
                    if ($this->effectiveDailyWinsUsedToday($awarded, $today) === 0) {
                        $awarded->daily_wins_count = 1;
                        $awarded->daily_wins_count_date = $today;
                    } else {
                        $awarded->daily_wins_count = ($awarded->daily_wins_count ?? 0) + 1;
                    }
                    $awarded->save();
                }

                $game->prize_id = $winningPrizeId;
                $game->finished_at = now();
                $game->save();
                $payload['message'] = 'You won a prize!';
            }

            return $payload;
        });
    }

    private function validateGameCanProceed(?Game $game): Game
    {
        if ($game === null) {
            throw new GameValidationException('Game not found.');
        }

        if ($game->finished_at !== null) {
            throw new GameValidationException('This game has already ended.');
        }

        $game->loadMissing('campaign');
        $campaign = $game->campaign;
        if ($campaign === null) {
            throw new GameValidationException('Game must belong to a campaign.');
        }

        return $game;
    }

    private function validatePrizeAvailableForDraw(?Prize $prize): Prize
    {
        if ($prize === null) {
            throw new GameValidationException('No prize is available for this draw.');
        }

        return $prize;
    }

    private function validateDailyWinLimitAllowsCompletion(Game $game, Prize $prize): void
    {
        $samePrizeReveals = GameRevealedTile::query()
            ->where('game_id', $game->id)
            ->where('prize_id', $prize->id)
            ->count();
        $wouldCompleteWin = ($samePrizeReveals + 1) >= 3;

        if (! $wouldCompleteWin || $prize->daily_wins_limit === null) {
            return;
        }

        $limited = Prize::query()->whereKey($prize->id)->lockForUpdate()->first();
        if ($limited === null) {
            return;
        }

        $today = now()->toDateString();
        $used = $this->effectiveDailyWinsUsedToday($limited, $today);
        if ($used >= $limited->daily_wins_limit) {
            throw new GameValidationException('The daily limit for this prize was reached.');
        }
    }

    private function effectiveDailyWinsUsedToday(Prize $prize, string $today): int
    {
        $storedDate = $prize->daily_wins_count_date;
        if ($storedDate === null) {
            return 0;
        }

        $dateString = $storedDate instanceof \DateTimeInterface
            ? $storedDate->format('Y-m-d')
            : (string) $storedDate;

        if ($dateString !== $today) {
            return 0;
        }

        return (int) ($prize->daily_wins_count ?? 0);
    }

    private function findWinningPrizeId(int $gameId): ?int
    {
        $row = GameRevealedTile::query()
            ->where('game_id', $gameId)
            ->selectRaw('prize_id')
            ->groupBy('prize_id')
            ->havingRaw('COUNT(*) >= ?', [3])
            ->first();

        return $row !== null ? (int) $row->prize_id : null;
    }

    private function tileImageFromPrize(?string $image): string
    {
        if ($image === null || $image === '') {
            return self::FALLBACK_TILE_IMAGE;
        }

        return $image;
    }
}
