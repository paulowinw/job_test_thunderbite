<?php

namespace App\Exceptions;

use RuntimeException;

class GameValidationException extends RuntimeException
{
    public const FALLBACK_TILE_IMAGE = '/assets/1.png';

    public function __construct(
        string $message,
        public readonly string $tileImage = self::FALLBACK_TILE_IMAGE,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
