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

    public ?string $selectedDeviceId = null;

    public function mount(): void
    {
        $this->selectedDeviceId = request()->query('device_id');
    }

    public function refreshPoints(): void
    {
        if (! $this->selectedDeviceId) {
            return;
        }

        $points = $this->getPoints();

        $this->dispatch('gps-points-updated', points: $points);
    }

    protected function getViewData(): array
    {
        $devices = Device::query()
            ->with('user')
            ->orderBy('imei')
            ->get();

        return [
            'devices'          => $devices,
            'selectedDeviceId' => $this->selectedDeviceId,
            'initialPoints'    => $this->selectedDeviceId ? $this->getPoints() : [],
        ];
    }

    private function getPoints(): array
    {
        return GpsTrack::query()
            ->where('device_id', $this->selectedDeviceId)
            ->orderByDesc('time')
            ->limit(10)
            ->get()
            ->reverse()
            ->values()
            ->toArray();
    }
}
