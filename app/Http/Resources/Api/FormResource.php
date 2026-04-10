<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'is_active' => $this->resource->is_active,
            'current_version' => $this->resource->currentVersion ? [
                'id' => (string) $this->resource->currentVersion->getKey(),
                'version_number' => $this->resource->currentVersion->version_number,
                'published_at' => $this->resource->currentVersion->published_at?->toISOString(),
                'schema' => $this->resource->currentVersion->schema_snapshot,
            ] : null,
        ];
    }
}
