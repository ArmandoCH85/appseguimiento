<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Enums\FormFieldType;
use App\Filament\Tenant\Resources\SubmissionResource\Pages;
use App\Models\Tenant\Submission;
use App\Models\Tenant\SubmissionResponse;
use Carbon\Carbon;
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
                        ->placeholder('Sin datos'),
                ])
                ->columns(2),

            Section::make('Respuestas')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema(fn (?Submission $record): array => static::buildResponseEntries($record)),
        ]);
    }

    /**
     * Construye las entradas de respuesta para el infolist.
     * Maneja JSON decoding para arrays (checkbox), descarga para archivos,
     * y mapeo de valores a labels para opciones.
     *
     * @return array<int, mixed>
     */
    protected static function buildResponseEntries(?Submission $record): array
    {
        if (! $record) {
            return [];
        }

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
            ->map(fn (SubmissionResponse $response) => static::buildResponseEntry($response, $optionsMap, $record))
            ->all();
    }

    /**
     * Construye una TextEntry según el tipo de campo.
     */
    protected static function buildResponseEntry(
        SubmissionResponse $response,
        array $optionsMap,
        Submission $submission,
    ): TextEntry {
        $fieldName = $response->field_name;
        $fieldType = $response->field_type;
        $value = $response->value;
        $options = $optionsMap[$fieldName] ?? [];

        // Label legible
        $label = static::getFieldLabel($submission, $fieldName);

        return match ($fieldType) {
            FormFieldType::Checkbox->value => static::buildArrayEntry($label, $value, $options),

            FormFieldType::File->value => static::buildFileEntry($label, $value, $submission),

            FormFieldType::Select->value, FormFieldType::Radio->value => static::buildOptionEntry($label, $value, $options),

            FormFieldType::Date->value => TextEntry::make('response_'.$fieldName)
                ->label($label)
                ->state(static::formatDate($value)),

            FormFieldType::Time->value => TextEntry::make('response_'.$fieldName)
                ->label($label)
                ->state(static::formatTime($value)),

            default => TextEntry::make('response_'.$fieldName)
                ->label($label)
                ->state($value)
                ->placeholder('Sin respuesta'),
        };
    }

    /**
     * Obtiene el label legible de un campo desde el formulario.
     */
    protected static function getFieldLabel(Submission $submission, string $fieldName): string
    {
        $fields = $submission->formVersion?->form?->fields ?? collect();

        return $fields->where('name', $fieldName)->first()?->label ?? $fieldName;
    }

    /**
     * Construye una entrada para valores de array (Checkbox).
     */
    protected static function buildArrayEntry(string $label, ?string $value, array $options): TextEntry
    {
        $decoded = json_decode($value, true);

        if (! is_array($decoded) || $decoded === []) {
            return TextEntry::make('response_'.$label)
                ->label($label)
                ->state('Sin selección')
                ->placeholder('Sin respuesta');
        }

        $items = array_map(
            fn ($v) => $options[$v] ?? $v,
            $decoded,
        );

        $html = '<ul class="list-disc list-inside space-y-0.5 text-sm text-gray-800">';
        foreach ($items as $item) {
            $html .= '<li>'.e($item).'</li>';
        }
        $html .= '</ul>';

        return TextEntry::make('response_'.$label)
            ->label($label)
            ->state(new HtmlString($html))
            ->html();
    }

    /**
     * Construye una entrada para opciones (Select/Radio).
     */
    protected static function buildOptionEntry(string $label, ?string $value, array $options): TextEntry
    {
        if ($value === '' || $value === null) {
            return TextEntry::make('response_'.$label)
                ->label($label)
                ->state('Sin respuesta')
                ->placeholder('Sin respuesta');
        }

        $display = $options[$value] ?? $value;

        return TextEntry::make('response_'.$label)
            ->label($label)
            ->state($display);
    }

    /**
     * Construye una entrada con enlace de descarga para archivos.
     * Soporta archivos simples o múltiples (JSON array).
     */
    protected static function buildFileEntry(string $label, ?string $value, Submission $submission): TextEntry
    {
        if (empty($value)) {
            return TextEntry::make('response_'.$label)
                ->label($label)
                ->state('Sin archivo')
                ->placeholder('Sin archivo');
        }

        // Detectar si es un array JSON (múltiples archivos)
        $decoded = json_decode($value, true);
        $fileNames = is_array($decoded) ? $decoded : [$value];

        $allMedia = $submission->getMedia('submissions');
        $htmlParts = [];

        foreach ($fileNames as $fileName) {
            $media = $allMedia->firstWhere('file_name', $fileName);

            if (! $media) {
                // Si no encontramos el media, mostramos el nombre igual
                $htmlParts[] = sprintf(
                    '<div class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 rounded-md text-sm text-gray-600">'.
                    '<span>%s</span>'.
                    '<span class="text-gray-400">(no disponible)</span>'.
                    '</div>',
                    e($fileName)
                );

                continue;
            }

            $url = route('tenant.submissions.files.show', [
                'submission' => $submission,
                'filename' => $fileName,
            ]);
            $size = static::formatFileSize($media->size);

            $htmlParts[] = sprintf(
                '<a href="%s" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-sm font-medium text-gray-800 transition-colors no-underline hover:no-underline">'.
                '<span>%s</span>'.
                '<span class="text-gray-500">(%s)</span>'.
                '<span class="text-gray-400">·</span>'.
                '<span class="text-primary-600 hover:underline">Descargar</span>'.
                '</a>',
                e($url),
                e($fileName),
                e($size)
            );
        }

        $html = '<div class="flex flex-wrap gap-2">'.implode('', $htmlParts).'</div>';

        return TextEntry::make('response_'.$label)
            ->label($label)
            ->state(new HtmlString($html))
            ->html();
    }

    /**
     * Formatea una fecha ISO a formato legible.
     */
    protected static function formatDate(?string $value): string
    {
        if (empty($value)) {
            return 'Sin fecha';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception) {
            return $value;
        }
    }

    /**
     * Formatea una hora ISO a formato legible.
     */
    protected static function formatTime(?string $value): string
    {
        if (empty($value)) {
            return 'Sin hora';
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Exception) {
            return $value;
        }
    }

    /**
     * Devuelve un icono según la extensión del archivo.
     */
    protected static function getFileIcon(string $filename): string
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => '📄',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' => '🖼️',
            'doc', 'docx' => '📝',
            'xls', 'xlsx' => '📊',
            'zip', 'rar', '7z' => '📦',
            default => '📎',
        };
    }

    /**
     * Formatea un tamaño de archivo a texto legible.
     */
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
