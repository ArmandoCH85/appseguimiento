<?php

declare(strict_types=1);

namespace App\Filament\Central\Resources;

use App\Filament\Central\Resources\TenantResource\Pages;
use App\Models\Central\Tenant;
use Filament\Forms\Get;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
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
        // Prioridad 1: Clave específica en el config (central_domain leerá de .env)
        if (is_string($domain = config('tenancy.central_domain')) && $domain !== '') {
            return trim($domain);
        }

        // Prioridad 2: Host actual (para que el formulario muestre el dominio real en uso)
        if (! app()->runningInConsole()) {
            $host = request()->getHost();
            if (is_string($host) && $host !== '' && ! filter_var($host, FILTER_VALIDATE_IP) && $host !== 'localhost') {
                return $host;
            }
        }

        // Prioridad 3: APP_URL
        $appUrlHost = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST);
        if (is_string($appUrlHost) && $appUrlHost !== '' && ! filter_var($appUrlHost, FILTER_VALIDATE_IP) && $appUrlHost !== 'localhost') {
            return $appUrlHost;
        }

        // Prioridad 4: Buscar en la lista de central_domains (excluyendo IPs y localhost)
        foreach (config('tenancy.central_domains', []) as $domain) {
            if (is_string($domain) && $domain !== '' && ! filter_var($domain, FILTER_VALIDATE_IP) && $domain !== 'localhost') {
                return $domain;
            }
        }

        return 'localhost';
    }

    public static function form(Schema $schema): Schema
    {
        $baseDomain = static::getBaseDomain();

        return $schema
            ->columns([
                'default' => 1,
                'xl' => 12,
            ])
            ->schema([
                Section::make('Empresa')
                    ->icon('heroicon-o-building-office')
                    ->description('Definí los datos que identifican a la empresa dentro del sistema.')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 8,
                    ])
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
                            })
                            ->helperText('Usá el nombre comercial o razón social que verá el equipo.')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 4,
                            ]),

                        TextInput::make('slug')
                            ->label('Identificador')
                            ->required()
                            ->maxLength(255)
                            ->unique(table: 'tenants', column: 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash'])
                            ->helperText('Se usa internamente y en URLs. No se puede cambiar luego.')
                            ->disabled(fn (string $operation): bool => $operation === 'edit')
                            ->dehydrated(fn (string $operation): bool => $operation === 'create')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 6,
                    ]),

                Group::make([
                    Section::make('Acceso por dominio')
                        ->icon('heroicon-o-globe-alt')
                        ->description("El tenant se abrirá como subdominio de {$baseDomain}.")
                        ->schema([
                            TextInput::make('subdomain')
                                ->label('Subdominio')
                                ->placeholder('acme')
                                ->required()
                                ->maxLength(100)
                                ->rules(['alpha_dash'])
                                ->suffix('.' . $baseDomain)
                                ->helperText('Solo ingresá la parte izquierda del dominio.')
                                ->live(debounce: 500)
                                ->rule(fn () => function (string $attribute, $value, $fail) use ($baseDomain) {
                                    $fullDomain = "{$value}.{$baseDomain}";

                                    if (\App\Models\Central\Domain::where('domain', $fullDomain)->exists()) {
                                        $fail("El subdominio \"{$value}\" ya está en uso.");
                                    }
                                })
                                ->disabled(fn (string $operation): bool => $operation === 'edit')
                                ->visible(fn (string $operation): bool => $operation === 'create'),

                            Placeholder::make('domain_preview')
                                ->label('Vista previa del dominio')
                                ->content(function (Get $get) use ($baseDomain): HtmlString {
                                    $subdomain = trim((string) $get('subdomain'));

                                    if ($subdomain === '') {
                                        return new HtmlString('<span class="text-gray-500">Ingresá un subdominio para ver el dominio completo.</span>');
                                    }

                                    return new HtmlString('<span class="font-mono text-sm">'.e("{$subdomain}.{$baseDomain}").'</span>');
                                })
                                ->visible(fn (string $operation): bool => $operation === 'create'),

                            Placeholder::make('domain_display')
                                ->label('Dominio principal')
                                ->content(fn ($record) => $record?->primary_domain ?? '—')
                                ->helperText('El dominio no puede modificarse después de la creación.')
                                ->visible(fn (string $operation): bool => $operation === 'edit'),
                        ]),

                    Section::make('Cuenta de Administrador Inicial')
                        ->icon('heroicon-o-key')
                        ->description('Se creará el primer usuario administrador dentro del tenant.')
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->schema([
                            TextInput::make('admin_email')
                                ->label('Email del administrador')
                                ->email()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->maxLength(255)
                                ->placeholder('admin@acme.com'),
                            TextInput::make('admin_password')
                                ->label('Contraseña del administrador')
                                ->password()
                                ->revealable()
                                ->required(fn (string $operation): bool => $operation === 'create')
                                ->minLength(8),
                        ])
                        ->columns(1),
                ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->columns(1),
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
