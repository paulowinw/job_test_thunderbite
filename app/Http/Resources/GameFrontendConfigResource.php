<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonException;

/** @mixin \App\Models\Game */
class GameFrontendConfigResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(
        $resource,
        private readonly array $revealedTiles,
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'apiPath' => '/api/flip',
            'gameId' => (string) $this->resource->id,
            'revealedTiles' => $this->revealedTiles,
        ];
    }

    /**
     * JSON string for embedding in the frontend view (throws on encode failure).
     *
     * @throws JsonException
     */
    public function toJsonString(Request $request): string
    {
        return json_encode($this->toArray($request), JSON_THROW_ON_ERROR);
    }
}
