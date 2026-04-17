<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenant\Device;
use App\Models\Tenant\Form;
use App\Models\Tenant\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Usuarios', User::count())
                ->description('Usuarios registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Formularios', Form::count())
                ->description('Formularios creados')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),

            Stat::make('Dispositivos registrados', Device::count())
                ->description('Total de dispositivos')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('gray'),

            Stat::make('Dispositivos transmitiendo', Device::whereHas('gpsTracks')->count())
                ->description('Con al menos 1 track GPS')
                ->descriptionIcon('heroicon-m-signal')
                ->color('warning'),
        ];
    }
}
