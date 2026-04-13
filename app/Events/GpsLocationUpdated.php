<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Tenant\GpsTrack;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GpsLocationUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $tenantId,
        public string $deviceId,
        public array $location,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("gps.tenant.{$this->tenantId}.device.{$this->deviceId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'location.update';
    }

    public function broadcastWith(): array
    {
        return $this->location;
    }
}
