<?php

namespace App\Services;

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

            if ($game === null) {
                return $this->fallbackResponse('Game not found.');
            }

            $existing = GameRevealedTile::query()
                ->where('game_id', $game->id)
                ->where('tile_index', $tileIndex)
                ->with('prize:id,image')
                ->first();

            if ($existing !== null) {
                return [
                    'tileImage' => $this->tileImageFromPrize($existing->prize?->image),
                ];
            }

            if ($game->finished_at !== null) {
                return $this->fallbackResponse('This game has already ended.');
            }

            $prize = $this->picker->pick($game);

            if ($prize === null) {
                return $this->fallbackResponse('No prize is available for this draw.');
            }

            $samePrizeReveals = GameRevealedTile::query()
                ->where('game_id', $game->id)
                ->where('prize_id', $prize->id)
                ->count();
            $wouldCompleteWin = ($samePrizeReveals + 1) >= 3;

            if ($wouldCompleteWin && $prize->daily_wins_limit !== null) {
                $limited = Prize::query()->whereKey($prize->id)->lockForUpdate()->first();
                if ($limited !== null) {
                    $used = $limited->daily_wins_count ?? 0;
                    if ($used >= $limited->daily_wins_limit) {
                        return $this->fallbackResponse('The daily limit for this prize was reached.');
                    }
                }
            }

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
                    $awarded->daily_wins_count = ($awarded->daily_wins_count ?? 0) + 1;
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

    /**
     * @return array{tileImage: string, message: string}
     */
    private function fallbackResponse(string $message): array
    {
        return [
            'tileImage' => self::FALLBACK_TILE_IMAGE,
            'message' => $message,
        ];
    }

    private function tileImageFromPrize(?string $image): string
    {
        if ($image === null || $image === '') {
            return self::FALLBACK_TILE_IMAGE;
        }

        return $image;
    }
}
