<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use Filament\Forms\Components\Select;
use Filament\Widgets\Concerns\InteractsWithForms;
use Filament\Widgets\Widget;
use Filament\Forms\Form;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class GpsLiveMapWidget extends Widget
{
    use InteractsWithForms;

    protected string $view = 'filament.tenant.widgets.gps-live-map';

    protected int|string|array $columnSpan = 'full';

    public ?string $deviceId = null;

    public function mount(): void
    {
        $this->deviceId = request()->query('device_id');
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('deviceId')
                ->label('Dispositivo')
                ->options(fn (): array => Device::query()
                    ->with('user')
                    ->orderBy('imei')
                    ->get()
                    ->mapWithKeys(fn ($d) => [$d->id => "{$d->imei} — {$d->user->name}"])
                    ->all()
                )
                ->searchable()
                ->live()
                ->placeholder('Seleccioná un dispositivo para ver su recorrido')
                ->columnSpanFull(),
        ]);
    }

    #[Computed]
    public function devices(): array
    {
        return Device::query()
            ->with('user')
            ->orderBy('imei')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->id => "{$d->imei} — {$d->user->name}"])
            ->all();
    }

    #[Computed]
    public function initialPoints(): array
    {
        if (! $this->deviceId) {
            return [];
        }

        return GpsTrack::query()
            ->where('device_id', $this->deviceId)
            ->orderBy('time')
            ->limit(500)
            ->get()
            ->toArray();
    }

    #[Computed]
    public function tenantId(): string
    {
        return tenant()->getTenantKey();
    }
}
