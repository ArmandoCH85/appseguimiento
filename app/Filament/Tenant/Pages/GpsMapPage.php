<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Pages;

use App\Filament\Tenant\Resources\GpsTrackResource;
use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class GpsMapPage extends Page
{
    protected string $view = 'filament.tenant.pages.gps-map';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoreo GPS';

    protected static ?string $navigationLabel = 'Mapa GPS';

    protected static ?string $title = 'Mapa GPS';

    protected static ?int $navigationSort = 10;

    protected Width | string | null $maxContentWidth = Width::Full;

    public ?string $selectedDeviceId = null;

    public ?string $lastUpdatedAt = null;

    public ?string $deviceGpsTime = null;

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->selectedDeviceId = request()->query('device_id');

        if ($this->selectedDeviceId) {
            $this->lastUpdatedAt = $this->limaTime();
            $this->deviceGpsTime = $this->getLatestGpsTime();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasPermissionTo('devices.view') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return 'Mapa GPS';
    }

    public function getSubheading(): ?string
    {
        return null;
    }

    public function updatedSelectedDeviceId(): void
    {
        $this->lastUpdatedAt = filled($this->selectedDeviceId) ? $this->limaTime() : null;
        $this->deviceGpsTime = $this->getLatestGpsTime();

        $this->dispatch(
            'gps-points-updated',
            points: $this->getPoints(),
            deviceName: $this->getSelectedDeviceName(),
            updatedAt: $this->lastUpdatedAt,
            deviceGpsTime: $this->deviceGpsTime,
            deviceId: $this->selectedDeviceId,
            shouldFit: true,
        );
    }

    public function refreshPoints(): void
    {
        if (! $this->selectedDeviceId) {
            return;
        }

        $this->lastUpdatedAt = $this->limaTime();
        $this->deviceGpsTime = $this->getLatestGpsTime();

        $this->dispatch(
            'gps-points-updated',
            points: $this->getPoints(),
            deviceName: $this->getSelectedDeviceName(),
            updatedAt: $this->lastUpdatedAt,
            deviceGpsTime: $this->deviceGpsTime,
            deviceId: $this->selectedDeviceId,
            shouldFit: false,
        );
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualizar ahora')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->disabled(fn (): bool => blank($this->selectedDeviceId))
                ->action(function (): void {
                    $this->refreshPoints();
                }),
            Action::make('table')
                ->label('Ver rastreo tabular')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(fn (): string => $this->getTabularTrackingUrl()),
        ];
    }

    protected function getViewData(): array
    {
        $devices = Device::query()->with('user')->orderBy('imei')->get();
        $selectedDevice = $this->getSelectedDevice();
        $mapPoints = filled($this->selectedDeviceId) ? $this->getPoints() : [];
        $recentPoints = filled($this->selectedDeviceId) ? $this->getRecentPoints() : [];
        $latestPoint = count($recentPoints) > 0 ? $recentPoints[0] : null;

        return [
            'devices' => $devices,
            'initialPoints' => $mapPoints,
            'selectedDeviceName' => filled($this->selectedDeviceId) ? $this->getSelectedDeviceName() : '',
            'selectedDevice' => $selectedDevice ? [
                'id' => $selectedDevice->getKey(),
                'imei' => $selectedDevice->imei,
                'user_name' => $selectedDevice->user?->name,
                'user_email' => $selectedDevice->user?->email,
            ] : null,
            'latestPoint' => $latestPoint,
            'pointsCount' => count($mapPoints),
            'recentPoints' => $recentPoints,
            'deviceGpsTime' => $this->deviceGpsTime,
        ];
    }

    private function limaTime(): string
    {
        return now()->setTimezone('America/Lima')->format('d/m/Y H:i:s');
    }

    private function getLatestGpsTime(): ?string
    {
        if (blank($this->selectedDeviceId)) {
            return null;
        }

        $latestTrack = GpsTrack::query()
            ->where('device_id', $this->selectedDeviceId)
            ->orderByDesc('time')
            ->first();

        if (! $latestTrack) {
            return null;
        }

        return now()
            ->setTimestamp((int) ($latestTrack->time / 1000))
            ->setTimezone('America/Lima')
            ->format('d/m/Y H:i:s');
    }

    private function getSelectedDeviceName(): string
    {
        $device = Device::query()->with('user')->find($this->selectedDeviceId);

        if (! $device) {
            return '';
        }

        return $device->imei . ($device->user ? ' · ' . $device->user->name : '');
    }

    private function getSelectedDevice(): ?Device
    {
        if (blank($this->selectedDeviceId)) {
            return null;
        }

        return Device::query()
            ->with('user')
            ->find($this->selectedDeviceId);
    }

    private function getPoints(): array
    {
        if (blank($this->selectedDeviceId)) {
            return [];
        }

        return GpsTrack::query()
            ->where('device_id', $this->selectedDeviceId)
            ->orderByDesc('time')
            ->limit(10)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (GpsTrack $track): array => $this->mapTrack($track, false))
            ->all();
    }

    private function getRecentPoints(): array
    {
        if (blank($this->selectedDeviceId)) {
            return [];
        }

        return GpsTrack::query()
            ->where('device_id', $this->selectedDeviceId)
            ->orderByDesc('time')
            ->limit(10)
            ->get()
            ->values()
            ->map(fn (GpsTrack $track, int $index): array => $this->mapTrack($track, $index === 0))
            ->all();
    }

    private function mapTrack(GpsTrack $track, bool $isLatest): array
    {
        return [
            'id' => $track->getKey(),
            'latitude' => (float) $track->latitude,
            'longitude' => (float) $track->longitude,
            'accuracy' => $track->accuracy,
            'accuracy_human' => "{$track->accuracy} m",
            'time' => $track->time,
            'time_human' => now()
                ->setTimestamp((int) ($track->time / 1000))
                ->setTimezone('America/Lima')
                ->format('d/m/Y H:i:s'),
            'is_latest' => $isLatest,
        ];
    }

    private function getTabularTrackingUrl(): string
    {
        if (blank($this->selectedDeviceId)) {
            return GpsTrackResource::getUrl('index');
        }

        return GpsTrackResource::getUrl('index', [
            'tableFilters' => [
                'device_id' => [
                    'value' => $this->selectedDeviceId,
                ],
            ],
        ]);
    }
}
