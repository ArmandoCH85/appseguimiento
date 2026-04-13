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

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function mount(): void
    {
        $this->selectedDeviceId = request()->query('device_id');
    }

    public function refreshPoints(): void
    {
        if (! $this->selectedDeviceId) {
            return;
        }

        $this->dispatch('gps-points-updated', points: $this->getPoints());
    }

    protected function getViewData(): array
    {
        return [
            'devices'          => Device::query()->with('user')->orderBy('imei')->get(),
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
