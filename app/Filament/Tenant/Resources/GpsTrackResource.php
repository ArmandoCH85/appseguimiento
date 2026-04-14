<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\GpsTrackResource\Pages;
use App\Models\Tenant\Device;
use App\Models\Tenant\GpsTrack;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class GpsTrackResource extends Resource
{
    protected static ?string $model = GpsTrack::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoreo GPS';

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
                    ->label('Dispositivo')
                    ->icon('heroicon-m-device-phone-mobile')
                    ->description(fn (GpsTrack $record): string => $record->device?->user?->name ?? 'Sin usuario asignado')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                TextColumn::make('latitude')
                    ->label('Coordenadas')
                    ->icon('heroicon-m-map-pin')
                    ->formatStateUsing(fn (string $state): string => number_format((float) $state, 6))
                    ->description(fn (GpsTrack $record): string => 'Lng: ' . number_format((float) $record->longitude, 6))
                    ->copyable()
                    ->copyableState(fn (GpsTrack $record): string => "{$record->latitude},{$record->longitude}"),

                TextColumn::make('accuracy')
                    ->label('Precisión')
                    ->icon('heroicon-m-signal')
                    ->formatStateUsing(fn (int $state): string => "{$state} m")
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 20  => 'success',
                        $state <= 100 => 'warning',
                        default       => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('time')
                    ->label('Hora GPS')
                    ->icon('heroicon-m-clock')
                    ->formatStateUsing(fn (int $state): string => Carbon::createFromTimestampMs($state)->setTimezone('America/Lima')->format('d/m/Y H:i:s'))
                    ->description(fn (GpsTrack $record): string => Carbon::createFromTimestampMs($record->time)->setTimezone('America/Lima')->diffForHumans())
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->icon('heroicon-m-server')
                    ->dateTime('d/m/Y H:i:s', timezone: 'America/Lima')
                    ->description(fn (GpsTrack $record): string => $record->created_at->setTimezone('America/Lima')->diffForHumans())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('device_id')
                    ->label('Dispositivo')
                    ->options(fn (): array => Device::query()
                        ->with('user')
                        ->get()
                        ->mapWithKeys(fn (Device $d) => [$d->id => $d->imei . ($d->user ? ' · ' . $d->user->name : '')])
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
                                $q->where('time', '>=', now()->parse($date)->startOfDay()->timestamp * 1000);
                            })
                            ->when($data['hasta'] ?? null, function ($q, $date) {
                                $q->where('time', '<=', now()->parse($date)->endOfDay()->timestamp * 1000);
                            });
                    }),
            ])
            ->defaultSort('time', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Dispositivo')
                ->icon('heroicon-o-device-phone-mobile')
                ->description('Información del equipo y su usuario asignado')
                ->schema([
                    TextEntry::make('device.imei')
                        ->label('IMEI')
                        ->icon('heroicon-m-device-phone-mobile')
                        ->copyable(),
                    TextEntry::make('device.user.name')
                        ->label('Usuario asignado')
                        ->icon('heroicon-m-user')
                        ->placeholder('Sin usuario'),
                    TextEntry::make('device.user.email')
                        ->label('Email')
                        ->icon('heroicon-m-envelope')
                        ->placeholder('Sin email')
                        ->copyable(),
                ])
                ->columns(3),

            Section::make('Ubicación')
                ->icon('heroicon-o-map-pin')
                ->description('Coordenadas reportadas por el dispositivo')
                ->schema([
                    TextEntry::make('latitude')
                        ->label('Latitud')
                        ->icon('heroicon-m-arrows-up-down')
                        ->copyable(),
                    TextEntry::make('longitude')
                        ->label('Longitud')
                        ->icon('heroicon-m-arrows-right-left')
                        ->copyable(),
                    TextEntry::make('accuracy')
                        ->label('Precisión')
                        ->icon('heroicon-m-signal')
                        ->formatStateUsing(fn (int $state): string => "{$state} metros")
                        ->badge()
                        ->color(fn (int $state): string => match (true) {
                            $state <= 20  => 'success',
                            $state <= 100 => 'warning',
                            default       => 'danger',
                        }),
                ])
                ->columns(3),

            Section::make('Tiempos')
                ->icon('heroicon-o-clock')
                ->description('Marcas de tiempo en zona horaria América/Lima')
                ->schema([
                    TextEntry::make('time')
                        ->label('Hora GPS')
                        ->icon('heroicon-m-clock')
                        ->formatStateUsing(fn (int $state): string => Carbon::createFromTimestampMs($state)->setTimezone('America/Lima')->format('d/m/Y H:i:s'))
                        ->helperText(fn (GpsTrack $record): string => Carbon::createFromTimestampMs($record->time)->setTimezone('America/Lima')->diffForHumans()),
                    TextEntry::make('elapsed_realtime_millis')
                        ->label('Tiempo desde boot')
                        ->icon('heroicon-m-arrow-path')
                        ->formatStateUsing(function (int $state): string {
                            $seconds = (int) ($state / 1000);
                            $h = intdiv($seconds, 3600);
                            $m = intdiv($seconds % 3600, 60);
                            $s = $seconds % 60;

                            return $h > 0
                                ? "{$h}h {$m}m {$s}s"
                                : ($m > 0 ? "{$m}m {$s}s" : "{$s}s");
                        })
                        ->helperText('Tiempo transcurrido desde el último reinicio del dispositivo'),
                    TextEntry::make('created_at')
                        ->label('Registrado en sistema')
                        ->icon('heroicon-m-server')
                        ->dateTime('d/m/Y H:i:s', timezone: 'America/Lima')
                        ->helperText(fn (GpsTrack $record): string => $record->created_at->setTimezone('America/Lima')->diffForHumans()),
                ])
                ->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGpsTracks::route('/'),
            'view'  => Pages\ViewGpsTrack::route('/{record}'),
        ];
    }
}
