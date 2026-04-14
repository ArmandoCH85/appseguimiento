<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Enums\FormFieldType;
use App\Filament\Tenant\Resources\FormResource\Pages;
use App\Models\Tenant\Form;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class FormResource extends Resource
{
    protected static ?string $model = Form::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Formularios';

    protected static ?string $navigationLabel = 'Formularios';

    protected static ?string $modelLabel = 'Formulario';

    protected static ?string $pluralModelLabel = 'Formularios';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermissionTo('forms.view') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return static::basicDetailsForm($schema);
    }

    public static function basicDetailsForm(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Datos básicos')
                ->description('Primero definí el nombre y la descripción del formulario. Las preguntas se diseñan en el constructor.')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre del formulario')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Usá un nombre claro para que el equipo lo identifique rápido.'),
                    Textarea::make('description')
                        ->label('Descripción')
                        ->rows(4)
                        ->columnSpanFull()
                        ->helperText('Explicá brevemente para qué sirve este formulario y cuándo se debe usar.'),
                    Toggle::make('is_active')
                        ->label('Formulario activo')
                        ->default(true)
                        ->helperText('Podés desactivarlo temporalmente sin borrarlo.'),
                ])
                ->columns(2),
        ]);
    }

    public static function editDetailsForm(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 12,
            ])
            ->schema([
                Section::make('Datos básicos')
                    ->description('Actualizá el contexto general del formulario. Las preguntas se siguen administrando desde el constructor.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 8,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del formulario')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Usá un nombre claro para que el equipo lo identifique rápido.')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 4,
                            ]),
                        Toggle::make('is_active')
                            ->label('Formulario activo')
                            ->default(true)
                            ->helperText('Si lo apagás, deja de estar disponible para nuevas asignaciones.')
                            ->columnSpan([
                                'default' => 1,
                                'lg' => 2,
                            ]),
                        Textarea::make('description')
                            ->label('Descripción operativa')
                            ->rows(10)
                            ->columnSpanFull()
                            ->helperText('Explicá el objetivo del formulario, cuándo usarlo y qué espera encontrar el operador.'),
                    ])
                    ->columns([
                        'default' => 1,
                        'lg' => 6,
                    ]),
                Group::make([
                    Section::make('Estado del formulario')
                        ->icon('heroicon-o-signal')
                        ->compact()
                        ->schema([
                            Placeholder::make('status_summary')
                                ->label('Disponibilidad')
                                ->content(fn (?Form $record): string => $record?->is_active
                                    ? 'Activo y listo para nuevas asignaciones.'
                                    : 'Inactivo. No aparecerá en nuevas asignaciones hasta volver a activarlo.'),
                            Placeholder::make('updated_at_summary')
                                ->label('Última modificación')
                                ->content(fn (?Form $record): string => $record?->updated_at?->diffForHumans() ?? 'Todavía sin cambios registrados.'),
                        ]),
                    Section::make('Versión actual')
                        ->icon('heroicon-o-book-open')
                        ->compact()
                        ->schema([
                            Placeholder::make('current_version_label')
                                ->label('Versión publicada')
                                ->content(fn (?Form $record): string => $record?->currentVersion?->version_number
                                    ? 'Versión '.$record->currentVersion->version_number
                                    : 'Borrador sin publicar'),
                            Placeholder::make('questions_count')
                                ->label('Preguntas configuradas')
                                ->content(fn (?Form $record): string => (string) ($record?->fields()->count() ?? 0)),
                        ]),
                ])
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 4,
                    ])
                    ->columns(1),
            ]);
    }

    public static function builderForm(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Preguntas del formulario')
                ->description('Acá diseñás las preguntas que va a responder el usuario final. Agregá, ordená y configurá cada campo.')
                ->schema([
                    Repeater::make('fields')
                        ->label('Campos')
                        ->relationship()
                        ->columnSpanFull()
                        ->defaultItems(0)
                        ->addActionLabel('Agregar pregunta')
                        ->reorderableWithButtons()
                        ->orderColumn('order')
                        ->itemLabel(fn (array $state): string => $state['label'] ?? 'Nueva pregunta')
                        ->schema([
                            Hidden::make('name')
                                ->dehydrated()
                                ->dehydrateStateUsing(fn ($state, callable $get): string => filled($state)
                                    ? (string) $state
                                    : Str::slug((string) $get('label'), '_')),
                            // ── Fila 1: pregunta + identificador ──
                            TextInput::make('label')
                                ->label('Pregunta visible')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                                    $current = trim((string) $get('name'));

                                    if ($current !== '') {
                                        return;
                                    }

                                    $set('name', Str::slug((string) $state, '_'));
                                })
                                ->helperText('Es el texto que verá la persona al completar el formulario.')
                                ->columnSpanFull(),

                            // ── Fila 2: tipo + toggles ──
                            Select::make('type')
                                ->label('Tipo de respuesta')
                                ->required()
                                ->options(static::fieldTypeOptions())
                                ->native(false)
                                ->live()
                                ->helperText('Elegí cómo querés que responda el usuario.')
                                ->columnSpan(1),
                            Toggle::make('is_required')
                                ->label('Obligatoria')
                                ->default(false)
                                ->columnSpan(1),
                            Toggle::make('is_active')
                                ->label('Campo activo')
                                ->default(true)
                                ->columnSpan(1),

                            // ── Configuración según tipo de respuesta ──

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::Text->value)
                                ->schema([
                                    TextInput::make('settings.max_length')
                                        ->label('Longitud máxima')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(65535)
                                        ->placeholder('255')
                                        ->helperText('Cantidad máxima de caracteres permitidos.'),
                                    TextInput::make('settings.placeholder')
                                        ->label('Placeholder')
                                        ->maxLength(255)
                                        ->placeholder('Ej: Ingresá tu nombre...')
                                        ->helperText('Texto que aparece en el campo antes de escribir.')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::Textarea->value)
                                ->schema([
                                    TextInput::make('settings.rows')
                                        ->label('Filas visibles')
                                        ->numeric()
                                        ->minValue(2)
                                        ->maxValue(20)
                                        ->placeholder('4')
                                        ->helperText('Cantidad de filas que se muestran.'),
                                    TextInput::make('settings.max_length')
                                        ->label('Longitud máxima')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(65535)
                                        ->placeholder('5000')
                                        ->helperText('Cantidad máxima de caracteres.'),
                                    TextInput::make('settings.placeholder')
                                        ->label('Placeholder')
                                        ->maxLength(255)
                                        ->placeholder('Ej: Describí el detalle...')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::Number->value)
                                ->schema([
                                    TextInput::make('settings.min_value')
                                        ->label('Valor mínimo')
                                        ->numeric()
                                        ->placeholder('Sin límite'),
                                    TextInput::make('settings.max_value')
                                        ->label('Valor máximo')
                                        ->numeric()
                                        ->placeholder('Sin límite'),
                                    TextInput::make('settings.step')
                                        ->label('Incremento')
                                        ->numeric()
                                        ->placeholder('1')
                                        ->helperText('Paso entre valores (ej: 0.01 para decimales).'),
                                    TextInput::make('settings.unit')
                                        ->label('Unidad')
                                        ->maxLength(20)
                                        ->placeholder('Ej: kg, m, %, $')
                                        ->helperText('Se muestra junto al campo como referencia.'),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::Select->value)
                                ->schema([
                                    Toggle::make('settings.searchable')
                                        ->label('Permitir búsqueda en la lista')
                                        ->default(false)
                                        ->helperText('Útil cuando hay muchas opciones.'),
                                    TextInput::make('settings.placeholder')
                                        ->label('Placeholder')
                                        ->maxLength(255)
                                        ->placeholder('Seleccioná una opción...')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::Radio->value)
                                ->schema([
                                    Toggle::make('settings.inline')
                                        ->label('Opciones en línea')
                                        ->default(false)
                                        ->helperText('Muestra las opciones en una fila en lugar de una columna.')
                                        ->columnSpanFull(),
                                ])
                                ->columns(1)
                                ->columnSpanFull(),

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::Checkbox->value)
                                ->schema([
                                    Toggle::make('settings.inline')
                                        ->label('Opciones en línea')
                                        ->default(false)
                                        ->helperText('Muestra las opciones en una fila en lugar de una columna.')
                                        ->columnSpanFull(),
                                ])
                                ->columns(1)
                                ->columnSpanFull(),

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::Date->value)
                                ->schema([
                                    TextInput::make('settings.min_date')
                                        ->label('Fecha mínima')
                                        ->placeholder('Ej: 2025-01-01 o hoy')
                                        ->helperText('Fecha desde la que se puede seleccionar.'),
                                    TextInput::make('settings.max_date')
                                        ->label('Fecha máxima')
                                        ->placeholder('Ej: 2025-12-31 o hoy+30d')
                                        ->helperText('Fecha hasta la que se puede seleccionar.'),
                                    TextInput::make('settings.placeholder')
                                        ->label('Placeholder')
                                        ->maxLength(255)
                                        ->placeholder('Seleccioná una fecha...')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::Time->value)
                                ->schema([
                                    TextInput::make('settings.min_time')
                                        ->label('Hora mínima')
                                        ->placeholder('Ej: 08:00')
                                        ->helperText('Hora desde la que se puede seleccionar.'),
                                    TextInput::make('settings.max_time')
                                        ->label('Hora máxima')
                                        ->placeholder('Ej: 20:00')
                                        ->helperText('Hora hasta la que se puede seleccionar.'),
                                    TextInput::make('settings.step_interval')
                                        ->label('Intervalo (minutos)')
                                        ->numeric()
                                        ->placeholder('15')
                                        ->helperText('Cada cuántos minutos se generan las opciones.')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make('Configuración del campo')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->compact()
                                ->visible(fn (callable $get): bool => $get('type') === FormFieldType::File->value)
                                ->schema([
                                    TagsInput::make('settings.accepted_file_types')
                                        ->label('Tipos de archivo aceptados')
                                        ->placeholder('pdf, jpg, png')
                                        ->helperText('Escribí cada extensión y presioná Enter.'),
                                    TextInput::make('settings.max_file_size')
                                        ->label('Tamaño máximo (KB)')
                                        ->numeric()
                                        ->placeholder('2048')
                                        ->helperText('Tamaño máximo del archivo en kilobytes.'),
                                    Toggle::make('settings.multiple_files')
                                        ->label('Permitir múltiples archivos')
                                        ->default(false)
                                        ->helperText('El usuario puede subir más de un archivo.')
                                        ->columnSpanFull(),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            // ── Opciones para Select / Radio / Checkbox ──

                            Repeater::make('options')
                                ->label('Opciones de respuesta')
                                ->relationship()
                                ->defaultItems(0)
                                ->minItems(1)
                                ->addActionLabel('Agregar opción')
                                ->reorderableWithButtons()
                                ->orderColumn('order')
                                ->visible(fn (callable $get): bool => in_array($get('type'), [
                                    FormFieldType::Select->value,
                                    FormFieldType::Radio->value,
                                    FormFieldType::Checkbox->value,
                                ], true))
                                ->helperText('Agregá las opciones que verá el usuario al completar el formulario.')
                                ->schema([
                                    TextInput::make('label')
                                        ->label('Texto visible')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('value')
                                        ->label('Valor interno')
                                        ->required()
                                        ->maxLength(255)
                                        ->helperText('Si lo dejás igual al texto visible, también está bien.'),
                                    Toggle::make('is_active')
                                        ->label('Opción activa')
                                        ->default(true),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            TagsInput::make('validation_rules')
                                ->label('Reglas avanzadas')
                                ->placeholder('max:255')
                                ->helperText('Opcional. Usalo solo si necesitás validaciones específicas.')
                                ->columnSpanFull(),
                        ])
                        ->columns(3),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Activo'),
                TextColumn::make('currentVersion.version_number')
                    ->label('Versión actual')
                    ->placeholder('Borrador'),
                TextColumn::make('updated_at')
                    ->label('Última modificación')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                // Solo quien puede diseñar/editar formularios ve estas acciones
                Action::make('preview')
                    ->label('Vista previa')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn (): bool => auth()->user()?->hasPermissionTo('forms.view') ?? false)
                    ->url(fn (Form $record): string => static::getUrl('preview', ['record' => $record])),
                Action::make('builder')
                    ->label('Diseñar')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (): bool => auth()->user()?->hasPermissionTo('forms.update') ?? false)
                    ->url(fn (Form $record): string => static::getUrl('builder', ['record' => $record])),
                EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->hasPermissionTo('forms.update') ?? false),

                // Solo operadores (o quien tenga submissions.create) ven la acción de responder
                Action::make('fill')
                    ->label('Responder')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->visible(fn (Form $record): bool => (auth()->user()?->hasPermissionTo('submissions.create') ?? false)
                        && $record->is_active
                        && $record->currentVersion !== null
                    )
                    ->url(fn (Form $record): string => static::getUrl('fill', ['record' => $record])),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListForms::route('/'),
            'create' => Pages\CreateForm::route('/create'),
            'edit' => Pages\EditForm::route('/{record}/edit'),
            'builder' => Pages\BuildForm::route('/{record}/builder'),
            'preview' => Pages\PreviewForm::route('/{record}/preview'),
            'fill' => Pages\FillForm::route('/{record}/fill'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function fieldTypeOptions(): array
    {
        return [
            FormFieldType::Text->value => 'Texto corto',
            FormFieldType::Textarea->value => 'Texto largo',
            FormFieldType::Number->value => 'Número',
            FormFieldType::Select->value => 'Lista desplegable',
            FormFieldType::Radio->value => 'Opción única',
            FormFieldType::Checkbox->value => 'Selección múltiple',
            FormFieldType::Date->value => 'Fecha',
            FormFieldType::Time->value => 'Hora',
            FormFieldType::File->value => 'Archivo',
        ];
    }
}
