<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\GpsTrackResource\Pages;
use App\Filament\Tenant\Widgets\GpsLiveMapWidget;
use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class GpsTrackResource extends Resource
{
    protected static ?string $model = GpsTrack::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Rastreo GPS';

    protected static ?string $modelLabel = 'Punto GPS';

    protected static ?string $pluralModelLabel = 'Rastreo GPS';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermissionTo('devices.view') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device.imei')
                    ->label('IMEI')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('device.user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Latitud')
                    ->sortable(),
                TextColumn::make('longitude')
                    ->label('Longitud')
                    ->sortable(),
                TextColumn::make('time')
                    ->label('Timestamp GPS')
                    ->formatStateUsing(fn (int $state): string => now()->setTimestampMs($state)->format('d/m/Y H:i:s'))
                    ->sortable(),
                TextColumn::make('accuracy')
                    ->label('Precisión')
                    ->formatStateUsing(fn (int $state): string => "{$state} m")
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('device_id')
                    ->label('Dispositivo')
                    ->options(fn (): array => Device::query()
                        ->with('user')
                        ->get()
                        ->pluck('imei', 'id')
                        ->all()
                    )
                    ->searchable()
                    ->preload(),
                Filter::make('time_range')
                    ->label('Rango de tiempo')
                    ->form([
                        DatePicker::make('desde')
                            ->label('Desde')
                            ->native(false),
                        DatePicker::make('hasta')
                            ->label('Hasta')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['desde'] ?? null, function ($q, $date) {
                                $from = now()->parse($date)->startOfDay()->timestamp * 1000;
                                $q->where('time', '>=', $from);
                            })
                            ->when($data['hasta'] ?? null, function ($q, $date) {
                                $to = now()->parse($date)->endOfDay()->timestamp * 1000;
                                $q->where('time', '<=', $to);
                            });
                    }),
            ])
            ->defaultSort('time', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Datos del dispositivo')
                ->icon('heroicon-o-device-phone-mobile')
                ->schema([
                    TextEntry::make('device.imei')
                        ->label('IMEI')
                        ->copyable(),
                    TextEntry::make('device.user.name')
                        ->label('Usuario asignado')
                        ->placeholder('Sin usuario'),
                    TextEntry::make('device.user.email')
                        ->label('Email del usuario')
                        ->placeholder('Sin email'),
                ])
                ->columns(3),

            Section::make('Ubicación')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    TextEntry::make('latitude')
                        ->label('Latitud'),
                    TextEntry::make('longitude')
                        ->label('Longitud'),
                    TextEntry::make('accuracy')
                        ->label('Precisión')
                        ->formatStateUsing(fn (int $state): string => "{$state} metros"),
                ])
                ->columns(3),

            Section::make('Tiempos')
                ->icon('heroicon-o-clock')
                ->schema([
                    TextEntry::make('time')
                        ->label('Timestamp GPS (epoch ms)')
                        ->formatStateUsing(fn (int $state): string => now()->setTimestampMs($state)->format('d/m/Y H:i:s')),
                    TextEntry::make('elapsed_realtime_millis')
                        ->label('Elapsed Realtime (ms desde boot)')
                        ->formatStateUsing(fn (int $state): string => number_format($state).' ms'),
                    TextEntry::make('created_at')
                        ->label('Registrado en sistema')
                        ->dateTime('d/m/Y H:i:s'),
                ])
                ->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGpsTracks::route('/'),
            'view' => Pages\ViewGpsTrack::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            GpsLiveMapWidget::class,
        ];
    }
}
