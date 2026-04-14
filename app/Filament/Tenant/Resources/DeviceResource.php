<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\DeviceResource\Pages;
use App\Models\Tenant\Device;
use App\Models\Tenant\User;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoreo GPS';

    protected static ?string $navigationLabel = 'Dispositivos';

    protected static ?string $modelLabel = 'Dispositivo';

    protected static ?string $pluralModelLabel = 'Dispositivos';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermissionTo('devices.view') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return static::editForm($schema);
    }

    public static function createForm(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Datos del dispositivo')
                ->description('Vinculá un dispositivo IMEI a un usuario operador.')
                ->icon('heroicon-o-device-phone-mobile')
                ->schema([
                    Select::make('user_id')
                        ->label('Usuario')
                        ->options(fn (): array => User::query()
                            ->where('is_active', true)
                            ->whereDoesntHave('device')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                        )
                        ->required()
                        ->searchable()
                        ->preload()
                        ->placeholder('Buscá un usuario...')
                        ->columnSpanFull()
                        ->helperText('Solo se muestran usuarios activos sin dispositivo asignado.'),

                    TextInput::make('imei')
                        ->label('IMEI')
                        ->required()
                        ->maxLength(15)
                        ->regex('/^[0-9]{15}$/')
                        ->placeholder('Ej: 123456789012345')
                        ->columnSpanFull()
                        ->helperText('Ingresá manualmente el IMEI del dispositivo.'),
                ]),
        ]);
    }

    public static function editForm(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Datos del dispositivo')
                ->description('Modificá el usuario o IMEI del dispositivo.')
                ->icon('heroicon-o-device-phone-mobile')
                ->schema([
                    Select::make('user_id')
                        ->label('Usuario')
                        ->options(fn (?Device $record): array => User::query()
                            ->where('is_active', true)
                            ->where(function ($query) use ($record) {
                                $query->whereDoesntHave('device')
                                    ->orWhere('id', $record?->user_id);
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                        )
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default(fn (?Device $record) => $record?->user_id),

                    TextInput::make('imei')
                        ->label('IMEI')
                        ->required()
                        ->maxLength(15)
                        ->regex('/^[0-9]{15}$/')
                        ->default(fn (?Device $record) => $record?->imei),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Device $record): string => $record->user->email),
                TextColumn::make('imei')
                    ->label('IMEI')
                    ->searchable()
                    ->copyable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Eliminar'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}
