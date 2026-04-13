<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use Filament\Widgets\Widget;

class GpsLiveMapWidget extends Widget
{
    protected string $view = 'filament.tenant.widgets.gps-live-map';

    protected int|string|array $columnSpan = 'full';

    public ?string $deviceId = null;

    public function mount(): void
    {
        $this->deviceId = request()->query('device_id');
    }

    protected function getViewData(): array
    {
        $devices = Device::query()
            ->with('user')
            ->orderBy('imei')
            ->get();

        $initialPoints = [];
        if ($this->deviceId) {
            $initialPoints = GpsTrack::query()
                ->where('device_id', $this->deviceId)
                ->orderByDesc('time')
                ->limit(500)
                ->get()
                ->reverse()
                ->values()
                ->toArray();
        }

        return [
            'devices' => $devices,
            'selectedDeviceId' => $this->deviceId,
            'initialPoints' => $initialPoints,
            'tenantId' => tenant()->getTenantKey(),
        ];
    }
}
