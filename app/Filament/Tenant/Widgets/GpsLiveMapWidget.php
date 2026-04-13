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

    protected function getViewData(): array
    {
        $devices = Device::query()
            ->with('user')
            ->orderBy('imei')
            ->get();

        $selectedDeviceId = request()->query('device_id');

        $initialPoints = [];
        if ($selectedDeviceId) {
            $initialPoints = GpsTrack::query()
                ->where('device_id', $selectedDeviceId)
                ->orderBy('time')
                ->limit(500)
                ->get()
                ->toArray();
        }

        return [
            'devices' => $devices,
            'selectedDeviceId' => $selectedDeviceId,
            'initialPoints' => $initialPoints,
            'tenantId' => tenant()->getTenantKey(),
        ];
    }
}
