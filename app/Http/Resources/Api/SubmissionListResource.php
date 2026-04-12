<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'form_version_id' => (string) $this->resource->form_version_id,
            'status' => $this->resource->status->value,
            'submitted_at' => $this->resource->submitted_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
