<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Prize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class WeightedPrizePicker
{
    /**
     * Eligible prizes for a weighted draw for this game.
     * {@see Prize::$daily_wins_limit} is enforced when a game is completed in
     * {@see \App\Services\RevealTileService::reveal()}.
     */
    public function eligibleQuery(Game $game): Builder
    {
        $game->loadMissing('campaign');
        $campaign = $game->campaign;

        if ($campaign === null) {
            throw new \InvalidArgumentException('Game must belong to a campaign.');
        }

        return Prize::query()->forWeightedPick($game);
    }

    /**
     * Pick one prize using weights, or null if the (possibly narrowed) set is empty.
     */
    public function pickFromQuery(Builder $query): ?Prize
    {
        if ($query->clone()->doesntExist()) {
            return null;
        }

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return $query->clone()
                ->orderByRaw('-LOG(RAND()) / weight DESC')
                ->limit(1)
                ->first();
        }

        return $this->pickUsingGumbelMax($query->clone());
    }

    public function pick(Game $game): ?Prize
    {
        return $this->pickFromQuery($this->eligibleQuery($game));
    }

    private function pickUsingGumbelMax(Builder $query): ?Prize
    {
        $prizes = $query->get();

        if ($prizes->isEmpty()) {
            return null;
        }

        $chosen = null;
        $bestKey = null;

        foreach ($prizes as $prize) {
            $weight = (float) $prize->weight;
            $u = (random_int(0, PHP_INT_MAX - 1) + 1) / PHP_INT_MAX;
            $key = -log($u) / $weight;

            if ($bestKey === null || $key > $bestKey) {
                $bestKey = $key;
                $chosen = $prize;
            }
        }

        return $chosen;
    }
}
