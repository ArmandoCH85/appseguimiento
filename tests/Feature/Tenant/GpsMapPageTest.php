<?php

declare(strict_types=1);

use App\Filament\Tenant\Pages\GpsMapPage;
use App\Models\Central\Tenant;
use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use App\Models\Tenant\User;
use Database\Seeders\TenantDatabaseSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('tenant'));
});

afterEach(fn () => dropCurrentTestTenantDatabases());

function createTenantWithGpsFixture(string $tenantId = 'gps-map-page'): Tenant
{
    $tenant = Tenant::create([
        'id' => $tenantId,
        'name' => 'GPS Map Tenant',
        'slug' => $tenantId,
    ]);

    $tenant->run(function (): void {
        app(TenantDatabaseSeeder::class)->run();
    });

    return $tenant;
}

function createTrackedDeviceWithPoints(int $points = 12): array
{
    $user = User::query()->where('email', 'supervisor@tenant.test')->firstOrFail();

    $device = Device::query()->create([
        'user_id' => $user->getKey(),
        'imei' => '123456789012345',
    ]);

    $baseTime = now()->startOfMinute()->subMinutes($points)->timestamp * 1000;

    for ($index = 0; $index < $points; $index++) {
        GpsTrack::query()->create([
            'device_id' => $device->getKey(),
            'latitude' => -12.046374 + ($index * 0.0001),
            'longitude' => -77.042793 + ($index * 0.0001),
            'time' => $baseTime + ($index * 60_000),
            'elapsed_realtime_millis' => 1_000_000 + ($index * 1_000),
            'accuracy' => 5,
        ]);
    }

    return [$user, $device];
}

it('renders an elegant empty state when no device is selected', function () {
    $tenant = createTenantWithGpsFixture('gps-map-empty');

    $tenant->run(function (): void {
        $supervisor = User::query()->where('email', 'supervisor@tenant.test')->firstOrFail();
        auth()->guard('web')->login($supervisor);

        Livewire::test(GpsMapPage::class)
            ->assertStatus(200)
            ->assertSee('Seguimiento en vivo')
            ->assertSee('Seleccioná un dispositivo para comenzar el monitoreo en vivo.')
            ->assertSee('Dispositivo actual')
            ->assertSee('Sin dispositivo');

        auth()->guard('web')->logout();
    });
});

it('renders selected device summary and recent gps activity', function () {
    $tenant = createTenantWithGpsFixture('gps-map-device');

    $tenant->run(function (): void {
        [$supervisor, $device] = createTrackedDeviceWithPoints();

        auth()->guard('web')->login($supervisor);

        Livewire::test(GpsMapPage::class)
            ->set('selectedDeviceId', $device->getKey())
            ->assertSet('selectedDeviceId', $device->getKey())
            ->assertSee('Último punto')
            ->assertSee('Actividad reciente')
            ->assertSee('Puntos visibles')
            ->assertSee($device->imei)
            ->assertSee($supervisor->name);

        auth()->guard('web')->logout();
    });
});

it('limits navigation and page access to users with devices.view permission and exposes the workspace actions', function () {
    $tenant = createTenantWithGpsFixture('gps-map-access');

    $tenant->run(function (): void {
        $supervisor = User::query()->where('email', 'supervisor@tenant.test')->firstOrFail();
        $operator = User::query()->where('email', 'operador@tenant.test')->firstOrFail();

        auth()->guard('web')->login($supervisor);

        expect(GpsMapPage::canAccess())->toBeTrue()
            ->and(GpsMapPage::shouldRegisterNavigation())->toBeTrue();

        $page = new class extends GpsMapPage
        {
            public function exposeHeaderActions(): array
            {
                return $this->getHeaderActions();
            }
        };

        expect(array_map(fn ($action) => $action->getName(), $page->exposeHeaderActions()))
            ->toContain('refresh')
            ->toContain('table');

        auth()->guard('web')->logout();
        auth()->guard('web')->login($operator);

        expect(GpsMapPage::canAccess())->toBeFalse()
            ->and(GpsMapPage::shouldRegisterNavigation())->toBeFalse();

        auth()->guard('web')->logout();
    });
});
