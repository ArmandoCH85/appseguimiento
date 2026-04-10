<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\TenantResource\Pages;
use App\Models\Central\Tenant;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Empresas';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $pluralModelLabel = 'Empresas';

    /**
     * Retorna el dominio base del sistema leyendo config/tenancy.php.
     * Descarta IPs y localhost — devuelve el primer dominio real.
     * Ejemplo: "appseguimiento.test"
     */
    public static function getBaseDomain(): string
    {
        $central = config('tenancy.central_domains', []);

        foreach ($central as $domain) {
            if (! filter_var($domain, FILTER_VALIDATE_IP) && $domain !== 'localhost') {
                return $domain;
            }
        }

        // Fallback: derivar del APP_URL
        return parse_url(config('app.url', 'http://localhost'), PHP_URL_HOST) ?? 'localhost';
    }

    public static function form(Schema $schema): Schema
    {
        $baseDomain = static::getBaseDomain();

        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Nombre de empresa')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, $state, callable $set) {
                        if ($operation === 'create') {
                            $slug = Str::slug($state);
                            $set('slug', $slug);
                            $set('subdomain', $slug);
                        }
                    }),

                TextInput::make('slug')
                    ->label('Identificador (slug)')
                    ->required()
                    ->maxLength(255)
                    ->unique(table: 'tenants', column: 'slug', ignoreRecord: true)
                    ->rules(['alpha_dash'])
                    ->helperText('Identificador único. No puede cambiarse después de la creación.')
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),

                TextInput::make('subdomain')
                    ->label('Subdominio')
                    ->required()
                    ->maxLength(100)
                    ->rules(['alpha_dash'])
                    ->suffix('.' . $baseDomain)
                    ->helperText("Solo ingresá el subdominio. El dominio completo será: {subdominio}.{$baseDomain}")
                    // Validar unicidad del dominio ensamblado directamente en el campo
                    ->rule(fn () => function (string $attribute, $value, $fail) use ($baseDomain) {
                        $fullDomain = "{$value}.{$baseDomain}";
                        if (\App\Models\Central\Domain::where('domain', $fullDomain)->exists()) {
                            $fail("El subdominio \"{$value}\" ya está en uso.");
                        }
                    })
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                    ->visible(fn (string $operation): bool => $operation === 'create'),
                    // Nota: NO usar dehydrated(false) aquí — el campo debe estar en el state
                    // para que las validaciones custom se ejecuten correctamente.
                    // El campo se elimina del payload en CreateTenant::mutateFormDataBeforeCreate.

                // En edición: mostrar el dominio actual como texto informativo (no editable por ahora)
                Placeholder::make('domain_display')
                    ->label('Dominio principal')
                    ->content(fn ($record) => $record?->primary_domain ?? '—')
                    ->helperText('El dominio no puede modificarse después de la creación.')
                    ->visible(fn (string $operation): bool => $operation === 'edit'),

                Section::make('Cuenta de Administrador Inicial')
                    ->description('Estas credenciales se usarán para crear el primer usuario administrador dentro de la base de datos del tenant.')
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->schema([
                        TextInput::make('admin_email')
                            ->label('Email del administrador')
                            ->email()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->maxLength(255),
                        TextInput::make('admin_password')
                            ->label('Contraseña del administrador')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Identificador')
                    ->searchable(),
                TextColumn::make('primary_domain')
                    ->label('Dominio')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
