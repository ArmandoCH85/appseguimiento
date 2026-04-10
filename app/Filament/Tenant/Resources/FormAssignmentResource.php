<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\FormAssignmentResource\Pages;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormAssignment;
use App\Models\Tenant\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class FormAssignmentResource extends Resource
{
    protected static ?string $model = FormAssignment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationLabel = 'Asignaciones';

    protected static ?string $modelLabel = 'Asignación';

    protected static ?string $pluralModelLabel = 'Asignaciones';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermissionTo('assignments.view') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return static::editForm($schema);
    }

    public static function createForm(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Formulario a asignar')
                ->description('Elegí qué formulario van a completar los usuarios seleccionados.')
                ->icon('heroicon-o-clipboard-document-list')
                ->schema([
                    Select::make('form_id')
                        ->label('Formulario')
                        ->relationship('form', 'name', fn ($query) => $query->where('is_active', true)->whereNotNull('current_version_id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->placeholder('Buscá un formulario...')
                        ->columnSpanFull()
                        ->helperText('Solo se muestran formularios activos con versión publicada.'),

                    Placeholder::make('form_description')
                        ->label('Descripción del formulario')
                        ->content(function (callable $get): HtmlString|string {
                            $formId = $get('form_id');
                            if (! $formId) {
                                return new HtmlString('<span class="text-gray-400 text-sm italic">Seleccioná un formulario para ver su descripción.</span>');
                            }
                            $form = Form::find($formId);
                            if (! $form?->description) {
                                return new HtmlString('<span class="text-gray-400 text-sm italic">Este formulario no tiene descripción.</span>');
                            }

                            return new HtmlString('<span class="text-sm">' . e($form->description) . '</span>');
                        })
                        ->columnSpanFull()
                        ->visible(fn (callable $get): bool => filled($get('form_id'))),
                ]),

            Section::make('Usuarios')
                ->description('Seleccioná uno o varios operadores. Los que ya tienen el formulario asignado y activo no aparecen en la lista.')
                ->icon('heroicon-o-users')
                ->schema([
                    Select::make('user_ids')
                        ->label('Usuarios a asignar')
                        ->options(fn (): array => User::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()
                        )
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->live()
                        ->placeholder('Buscá usuarios...')
                        ->columnSpanFull()
                        ->helperText(function (callable $get): string {
                            $formId = $get('form_id');
                            if (! $formId) {
                                return 'Primero seleccioná un formulario.';
                            }

                            $assignedCount = FormAssignment::query()
                                ->where('form_id', $formId)
                                ->whereNull('revoked_at')
                                ->count();

                            return $assignedCount > 0
                                ? "{$assignedCount} usuario(s) ya tienen este formulario activo. Si los seleccionás de nuevo, se ignoran automáticamente."
                                : 'Ningún usuario tiene este formulario asignado aún.';
                        }),
                ]),

            Section::make('Vigencia')
                ->description('Definí desde cuándo rige esta asignación.')
                ->icon('heroicon-o-calendar')
                ->collapsed()
                ->schema([
                    DateTimePicker::make('assigned_at')
                        ->label('Fecha de inicio')
                        ->required()
                        ->default(now())
                        ->native(false)
                        ->helperText('Por defecto se asigna desde ahora. Podés cambiarla si la asignación rige desde otra fecha.'),
                ]),
        ]);
    }

    public static function editForm(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('form_id')
                ->label('Formulario')
                ->relationship('form', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Select::make('user_id')
                ->label('Usuario')
                ->relationship('user', 'name')
                ->required()
                ->searchable()
                ->preload(),
            DateTimePicker::make('assigned_at')
                ->label('Fecha de asignación')
                ->required()
                ->default(now()),
            DateTimePicker::make('revoked_at')
                ->label('Fecha de revocación'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('form.name')
                    ->label('Formulario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->description(fn (FormAssignment $record): string => $record->user->email),
                TextColumn::make('assigned_at')
                    ->label('Asignado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('revoked_at')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state ? 'Revocado' : 'Activo')
                    ->badge()
                    ->color(fn (FormAssignment $record): string => $record->revoked_at ? 'danger' : 'success')
                    ->sortable(),
            ])
            ->actions([
                EditAction::make()->label('Editar'),
                Action::make('revoke')
                    ->label('Revocar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Revocar asignación?')
                    ->modalDescription('El usuario ya no podrá ver este formulario en la app.')
                    ->modalSubmitActionLabel('Sí, revocar')
                    ->visible(fn (FormAssignment $record): bool => $record->revoked_at === null)
                    ->action(function (FormAssignment $record): void {
                        $record->update(['revoked_at' => now()]);
                        Notification::make()
                            ->title('Asignación revocada')
                            ->warning()
                            ->send();
                    }),
                Action::make('reactivate')
                    ->label('Reactivar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (FormAssignment $record): bool => $record->revoked_at !== null)
                    ->action(function (FormAssignment $record): void {
                        $record->update(['revoked_at' => null]);
                        Notification::make()
                            ->title('Asignación reactivada')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('assigned_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFormAssignments::route('/'),
            'create' => Pages\CreateFormAssignment::route('/create'),
            'edit' => Pages\EditFormAssignment::route('/{record}/edit'),
        ];
    }
}
