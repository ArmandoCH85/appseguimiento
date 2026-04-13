<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class GpsMapPage extends Page
{
    protected string $view = 'filament.tenant.pages.gps-map';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Mapa GPS';

    protected static ?string $title = 'Mapa GPS';

    protected static ?int $navigationSort = 10;

    public ?string $selectedDeviceId = null;

    public ?string $lastUpdatedAt = null;

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->selectedDeviceId = request()->query('device_id');

        if ($this->selectedDeviceId) {
            $this->lastUpdatedAt = $this->limaTime();
        }
    }

    public function updatedSelectedDeviceId(): void
    {
        $this->lastUpdatedAt = $this->limaTime();
        $this->dispatch('gps-points-updated',
            points:     $this->getPoints(),
            deviceName: $this->getSelectedDeviceName(),
            updatedAt:  $this->lastUpdatedAt,
        );
    }

    public function refreshPoints(): void
    {
        if (! $this->selectedDeviceId) {
            return;
        }

        $this->lastUpdatedAt = $this->limaTime();
        $this->dispatch('gps-points-updated',
            points:     $this->getPoints(),
            deviceName: $this->getSelectedDeviceName(),
            updatedAt:  $this->lastUpdatedAt,
        );
    }

    private function limaTime(): string
    {
        return now()->setTimezone('America/Lima')->format('d/m/Y H:i:s');
    }

    private function getSelectedDeviceName(): string
    {
        $device = Device::query()->with('user')->find($this->selectedDeviceId);

        if (! $device) {
            return '';
        }

        return $device->imei . ($device->user ? ' · ' . $device->user->name : '');
    }

    protected function getViewData(): array
    {
        $devices = Device::query()->with('user')->orderBy('imei')->get();

        return [
            'devices'            => $devices,
            'initialPoints'      => $this->selectedDeviceId ? $this->getPoints() : [],
            'selectedDeviceName' => $this->selectedDeviceId ? $this->getSelectedDeviceName() : '',
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
