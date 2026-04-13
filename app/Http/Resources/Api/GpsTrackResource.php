<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GpsTrackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'device_id' => (string) $this->resource->device_id,
            'device' => [
                'imei' => $this->resource->device?->imei,
                'user' => $this->resource->device?->user?->name,
            ],
            'latitud' => $this->resource->latitude,
            'longitud' => $this->resource->longitude,
            'time' => $this->resource->time,
            'elapsedRealtimeMillis' => $this->resource->elapsed_realtime_millis,
            'accuracy' => $this->resource->accuracy,
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
