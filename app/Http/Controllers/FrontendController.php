<?php

namespace App\Http\Controllers;

use App\Http\Requests\Frontend\LoadCampaignRequest;
use App\Http\Resources\GameFrontendConfigResource;
use App\Models\Campaign;
use App\Services\ActiveGameResolver;
use App\Services\CampaignPublicPlayValidator;
use App\Services\GameRevealedTilesPayload;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function __construct(
        private readonly CampaignPublicPlayValidator $campaignPublicPlayValidator,
        private readonly ActiveGameResolver $activeGameResolver,
        private readonly GameRevealedTilesPayload $gameRevealedTilesPayload,
    ) {}

    public function loadCampaign(LoadCampaignRequest $request, Campaign $campaign): View
    {
        $validated = $request->validated();

        $this->campaignPublicPlayValidator->validate($campaign);

        $game = $this->activeGameResolver->resolveForAccountAndSegment(
            $campaign,
            $validated['a'],
            $validated['segment'],
        );

        $revealedTiles = $this->gameRevealedTilesPayload->forGame($game);

        $config = (new GameFrontendConfigResource($game, $revealedTiles))->toJsonString($request);

        return view('frontend.index', ['config' => $config]);
    }

    public function placeholder(): View
    {
        return view('frontend.placeholder');
    }
}
