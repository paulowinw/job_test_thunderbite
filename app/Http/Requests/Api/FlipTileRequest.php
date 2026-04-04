<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class FlipTileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gameId' => ['required', 'numeric', 'exists:games,id'],
            'tileIndex' => ['required', 'integer', 'min:0', 'max:24'],
        ];
    }
}
