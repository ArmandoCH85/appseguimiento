<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $status = $this->input('status', SubmissionStatus::PendingPhotos->value);
        $isDraft = $status === SubmissionStatus::Draft->value;

        return [
            'form_version_id' => ['required', 'string', 'exists:form_versions,id'],
            'idempotency_key' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in([
                SubmissionStatus::Draft->value,
                SubmissionStatus::PendingPhotos->value,
            ])],
            'latitude' => $isDraft ? ['nullable', 'numeric'] : ['required', 'numeric'],
            'longitude' => $isDraft ? ['nullable', 'numeric'] : ['required', 'numeric'],
            'responses' => $isDraft ? ['nullable', 'array'] : ['required', 'array'],
        ];
    }
}
