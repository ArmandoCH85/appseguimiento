<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [Rule::in([
                SubmissionStatus::Draft->value,
                SubmissionStatus::PendingPhotos->value,
            ])],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'responses' => ['nullable', 'array'],
        ];
    }
}
