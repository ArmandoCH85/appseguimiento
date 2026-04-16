<?php

declare(strict_types=1);

namespace App\Filament\Central\Widgets;

use App\Models\Central\CentralUser;
use App\Models\Central\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CentralStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Empresas activas', Tenant::count())
                ->description('Total de empresas registradas')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('Usuarios admin', CentralUser::count())
                ->description('Administradores centrales')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Nuevas empresas este mes', Tenant::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count())
                ->description('Creadas en ' . now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('warning'),
        ];
    }
}
