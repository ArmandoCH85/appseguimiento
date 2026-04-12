<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'file_name' => $this->resource->file_name,
            'mime_type' => $this->resource->mime_type,
            'size' => $this->resource->size,
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
