<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\UserResource\Pages;
use App\Models\Tenant\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermissionTo('users.view') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Datos personales')
                ->description('Definí cómo se identifica la persona dentro del tenant.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre completo')
                        ->placeholder('Ej.: María Pérez')
                        ->required()
                        ->maxLength(255)
                        ->autocomplete('name'),
                    TextInput::make('email')
                        ->label('Correo electrónico')
                        ->placeholder('usuario@empresa.com')
                        ->required()
                        ->email()
                        ->unique(ignoreRecord: true)
                        ->autocomplete('email')
                        ->helperText('Este correo se usará para iniciar sesión en el panel tenant.'),
                ])
                ->columns(2),
            Section::make('Acceso')
                ->description('Controlá la contraseña inicial y si el usuario puede ingresar al sistema.')
                ->schema([
                    TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->autocomplete('new-password')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->helperText('En edición, dejala vacía si querés conservar la contraseña actual.'),
                    Toggle::make('is_active')
                        ->label('Usuario activo')
                        ->default(true)
                        ->helperText('Si lo desactivás, no podrá iniciar sesión.'),
                ])
                ->columns(2),
            Section::make('Permisos')
                ->description('Asigná el rol que define qué puede hacer este usuario dentro del tenant.')
                ->schema([
                    Select::make('roles')
                        ->label('Roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->helperText('Podés asignar uno o varios roles según el nivel de acceso que necesite.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
            TextColumn::make('email')->label('Correo')->searchable(),
            IconColumn::make('is_active')->label('Activo')->boolean(),
            TextColumn::make('roles.name')->label('Roles')->badge(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
