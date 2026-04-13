<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreGpsTrackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imei' => ['required', 'string', 'size:15'],
            'points' => ['required', 'array', 'min:1'],
            'points.*.latitud' => ['required', 'numeric'],
            'points.*.longitud' => ['required', 'numeric'],
            'points.*.time' => ['required', 'integer', 'min:0'],
            'points.*.elapsedRealtimeMillis' => ['required', 'integer', 'min:0'],
            'points.*.accuracy' => ['required', 'integer', 'min:0'],
        ];
    }
}
