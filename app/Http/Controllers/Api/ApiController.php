<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FlipTileRequest;
use App\Services\RevealTile;

class ApiController extends Controller
{
    public function __construct(
        private readonly RevealTile $revealTile,
    ) {}

    public function flip(FlipTileRequest $request)
    {
        $validated = $request->validated();

        return response()->json($this->revealTile->reveal(
            (int) $validated['gameId'],
            (int) $validated['tileIndex'],
        ));
    }
}
