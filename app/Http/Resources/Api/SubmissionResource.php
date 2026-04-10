<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'form_version_id' => (string) $this->resource->form_version_id,
            'user_id' => (string) $this->resource->user_id,
            'idempotency_key' => $this->resource->idempotency_key,
            'latitude' => (float) $this->resource->latitude,
            'longitude' => (float) $this->resource->longitude,
            'status' => $this->resource->status->value,
            'submitted_at' => $this->resource->submitted_at?->toISOString(),
            'responses' => $this->resource->responses->map(fn ($response): array => [
                'field_name' => $response->field_name,
                'field_type' => $response->field_type,
                'value' => $response->value,
            ])->values()->all(),
        ];
    }
}
