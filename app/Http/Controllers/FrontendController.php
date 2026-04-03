<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function loadCampaign(Request $request, Campaign $campaign): View
    {
        $validated = $request->validate([
            'a' => ['required', 'string', 'max:255'],
            'segment' => ['required', 'in:low,med,high'],
        ]);

        $game = Game::query()
            ->where('campaign_id', $campaign->id)
            ->where('account', $validated['a'])
            ->where('segment', $validated['segment'])
            ->whereNull('finished_at')
            ->latest('id')
            ->first();

        if (! $game) {
            $game = Game::create([
                'campaign_id' => $campaign->id,
                'account' => $validated['a'],
                'segment' => $validated['segment'],
            ]);
        }

        $revealedTiles = $game->revealedTiles()
            ->with(['prize:id,image'])
            ->orderBy('tile_index')
            ->get()
            ->map(fn ($tile) => [
                'index' => (int) $tile->tile_index,
                'image' => $tile->prize?->image,
            ])
            ->values()
            ->all();

        $jsonConfig = json_encode([
            'apiPath' => '/api/flip',
            'gameId' => (string) $game->id,
            'revealedTiles' => $revealedTiles,
        ], JSON_THROW_ON_ERROR);

        return view('frontend.index', ['config' => $jsonConfig]);
    }

    public function placeholder(): View
    {
        return view('frontend.placeholder');
    }
}
