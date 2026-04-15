<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Enums\FormFieldType;
use App\Filament\Tenant\Resources\SubmissionResource\Pages;
use App\Models\Tenant\Submission;
use App\Models\Tenant\SubmissionResponse;
use Carbon\Carbon;
use Filament\Actions\Action as InfolistAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Formularios';

    protected static ?string $navigationLabel = 'Respuestas';

    protected static ?string $modelLabel = 'Respuesta';

    protected static ?string $pluralModelLabel = 'Respuestas';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasPermissionTo('submissions.view') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('formVersion.form.name')
                ->label('Formulario')
                ->searchable()
                ->sortable(),
            TextColumn::make('user.name')
                ->label('Usuario')
                ->searchable()
                ->placeholder('Anónimo'),
            TextColumn::make('user.email')
                ->label('Email')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('status')
                ->label('Estado')
                ->badge(),
            TextColumn::make('submitted_at')
                ->label('Fecha de envío')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
            TextColumn::make('responses_count')
                ->label('Respuestas')
                ->counts('responses')
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                Filter::make('submitted_at')
                    ->label('Fecha de envío')
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
                            ->when($data['desde'] ?? null, fn ($q, $date) => $q->whereDate('submitted_at', '>=', $date))
                            ->when($data['hasta'] ?? null, fn ($q, $date) => $q->whereDate('submitted_at', '<=', $date));
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Datos del envío')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    TextEntry::make('formVersion.form.name')
                        ->label('Formulario'),
                    TextEntry::make('user.name')
                        ->label('Respondido por')
                        ->placeholder('Anónimo'),
                    TextEntry::make('user.email')
                        ->label('Email'),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge(),
                    TextEntry::make('submitted_at')
                        ->label('Fecha de envío')
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('latitude')
                        ->label('Latitud')
                        ->placeholder('Sin datos'),
                    TextEntry::make('longitude')
                        ->label('Longitud')
                        ->placeholder('Sin datos')
                        ->suffixAction(
                            InfolistAction::make('view_map')
                                ->label('Ver en mapa')
                                ->icon('heroicon-o-map')
                                ->color('primary')
                                ->modalHeading('Ubicación del Envío')
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel('Cerrar')
                                ->visible(fn (?Submission $record): bool => filled($record?->latitude) && filled($record?->longitude))
                                ->modalContent(fn (?Submission $record) => view('filament.tenant.components.submission-map', [
                                    'latitude' => $record?->latitude,
                                    'longitude' => $record?->longitude,
                                ]))
                        ),
                ])
                ->columns(2),

            Section::make('Respuestas')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema(function (?Submission $record) {
                    if (! $record) {
                        return [];
                    }

                    $cards = static::buildResponseCardsData($record);

                    return [
                        TextEntry::make('responses_display')
                            ->hiddenLabel()
                            ->state(new HtmlString(
                                view('filament.tenant.components.responses-cards', [
                                    'cards' => $cards,
                                ])->render()
                            ))
                            ->html(),
                    ];
                }),
        ]);
    }

    protected static function buildResponseCardsData(Submission $record): array
    {
        $record->loadMissing([
            'responses',
            'formVersion.form.fields.options',
        ]);

        $fields = $record->formVersion?->form?->fields ?? collect();
        $optionsMap = $fields
            ->keyBy('name')
            ->map(fn ($field) => $field->options
                ->where('is_active', true)
                ->pluck('label', 'value')
                ->all()
            )
            ->all();

        return $record->responses
            ->sortBy(fn (SubmissionResponse $r) => $r->field_name)
            ->map(function (SubmissionResponse $response) use ($optionsMap, $record, $fields): array {
                $fieldName = $response->field_name;
                $fieldType = $response->field_type;
                $value = $response->value;
                $options = $optionsMap[$fieldName] ?? [];
                $label = $fields->where('name', $fieldName)->first()?->label ?? $fieldName;
                $config = static::getFieldTypeConfig($fieldType);

                $valueResult = match ($fieldType) {
                    FormFieldType::Checkbox->value => static::resolveCheckboxValue($value, $options),
                    FormFieldType::File->value => static::resolveFileValue($value, $record),
                    FormFieldType::Select->value, FormFieldType::Radio->value => static::resolveOptionValue($value, $options),
                    FormFieldType::Date->value => static::resolveDateValue($value),
                    FormFieldType::Time->value => static::resolveTimeValue($value),
                    default => static::resolveTextValue($value),
                };

                return [
                    'label' => $label,
                    'icon' => $config['icon'],
                    'color' => $config['color'],
                    'type_label' => $config['type_label'],
                    'value_type' => $valueResult['type'],
                    'value_data' => $valueResult['data'],
                ];
            })
            ->values()
            ->all();
    }

    protected static function getFieldTypeConfig(string $fieldType): array
    {
        return match ($fieldType) {
            FormFieldType::Text->value => ['icon' => 'heroicon-o-document-text', 'color' => 'blue', 'type_label' => 'Texto'],
            FormFieldType::Textarea->value => ['icon' => 'heroicon-o-bars-3', 'color' => 'blue', 'type_label' => 'Texto'],
            FormFieldType::Number->value => ['icon' => 'heroicon-o-hashtag', 'color' => 'violet', 'type_label' => 'Num'],
            FormFieldType::Select->value => ['icon' => 'heroicon-o-chevron-down', 'color' => 'amber', 'type_label' => 'Lista'],
            FormFieldType::Radio->value => ['icon' => 'heroicon-o-signal', 'color' => 'amber', 'type_label' => 'Opción'],
            FormFieldType::Checkbox->value => ['icon' => 'heroicon-o-check-circle', 'color' => 'emerald', 'type_label' => 'Multi'],
            FormFieldType::Date->value => ['icon' => 'heroicon-o-calendar-days', 'color' => 'rose', 'type_label' => 'Fecha'],
            FormFieldType::Time->value => ['icon' => 'heroicon-o-clock', 'color' => 'indigo', 'type_label' => 'Hora'],
            FormFieldType::File->value => ['icon' => 'heroicon-o-paper-clip', 'color' => 'cyan', 'type_label' => 'Archivo'],
            default => ['icon' => 'heroicon-o-question-mark-circle', 'color' => 'gray', 'type_label' => ''],
        };
    }

    protected static function resolveCheckboxValue(?string $value, array $options): array
    {
        $decoded = json_decode($value ?? '', true);

        if (! is_array($decoded) || $decoded === []) {
            return ['type' => 'empty', 'data' => 'Sin selección'];
        }

        $items = array_map(fn ($v) => $options[$v] ?? $v, $decoded);

        return ['type' => 'checkbox', 'data' => $items];
    }

    protected static function resolveOptionValue(?string $value, array $options): array
    {
        if ($value === '' || $value === null) {
            return ['type' => 'empty', 'data' => 'Sin respuesta'];
        }

        $display = $options[$value] ?? $value;

        return ['type' => 'option', 'data' => $display];
    }

    protected static function resolveFileValue(?string $value, Submission $submission): array
    {
        $allMedia = $submission->getMedia('submissions');

        if (empty($value)) {
            if ($allMedia->isEmpty()) {
                return ['type' => 'empty', 'data' => 'Sin archivo'];
            }

            $value = json_encode($allMedia->pluck('file_name')->toArray());
        }

        $decoded = json_decode($value, true);
        $fileNames = is_array($decoded) ? $decoded : [$value];

        $files = [];

        foreach ($fileNames as $fileName) {
            $media = $allMedia->firstWhere('file_name', $fileName);

            if (! $media) {
                $files[] = [
                    'name' => $fileName,
                    'url' => null,
                    'size' => null,
                    'available' => false,
                ];

                continue;
            }

            $files[] = [
                'name' => $fileName,
                'url' => route('tenant.submissions.files.show', [
                    'submission' => $submission,
                    'filename' => $fileName,
                ]),
                'size' => static::formatFileSize($media->size),
                'available' => true,
            ];
        }

        return ['type' => 'file', 'data' => $files];
    }

    protected static function resolveDateValue(?string $value): array
    {
        if (empty($value)) {
            return ['type' => 'empty', 'data' => 'Sin fecha'];
        }

        try {
            return ['type' => 'date', 'data' => Carbon::parse($value)->format('d/m/Y')];
        } catch (\Exception) {
            return ['type' => 'text', 'data' => $value];
        }
    }

    protected static function resolveTimeValue(?string $value): array
    {
        if (empty($value)) {
            return ['type' => 'empty', 'data' => 'Sin hora'];
        }

        try {
            return ['type' => 'time', 'data' => Carbon::parse($value)->format('H:i')];
        } catch (\Exception) {
            return ['type' => 'text', 'data' => $value];
        }
    }

    protected static function resolveTextValue(?string $value): array
    {
        if ($value === '' || $value === null) {
            return ['type' => 'empty', 'data' => 'Sin respuesta'];
        }

        return ['type' => 'text', 'data' => $value];
    }

    protected static function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'view' => Pages\ViewSubmission::route('/{record}'),
        ];
    }
}
