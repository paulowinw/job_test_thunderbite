<?php

namespace App\Http\Controllers;

use App\Http\Resources\GameFrontendConfigResource;
use App\Models\Campaign;
use App\Services\GameLoader;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function __construct(
        private readonly GameLoader $gameLoader,
    ) {}

    public function loadCampaign(Request $request, Campaign $campaign): View
    {
        $validated = $request->validate([
            'a' => ['required', 'string', 'max:255'],
            'segment' => ['required', 'in:low,med,high'],
        ]);

        $this->gameLoader->validateCampaignPublicPlay($campaign);

        /** I'm using a service to load the game and revealed tiles, I could use Eloquent query builder instead,
         * but I think it's better to use a services because the project doesn't have Eloquent.
        */
        $game = $this->gameLoader->findOrCreateGame(
            $campaign,
            $validated['a'],
            $validated['segment'],
        );

        $revealedTiles = $this->gameLoader->revealedTiles($game);

        $config = (new GameFrontendConfigResource($game, $revealedTiles))->toJsonString($request);

        return view('frontend.index', ['config' => $config]);
    }

    public function placeholder(): View
    {
        return view('frontend.placeholder');
    }
}
