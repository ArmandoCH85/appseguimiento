<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormResource\Pages;

use App\Enums\FormFieldType;
use App\Enums\SubmissionStatus;
use App\Filament\Tenant\Resources\FormResource;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormField;
use App\Models\Tenant\Submission;
use App\Models\Tenant\SubmissionResponse;
use App\Models\Tenant\User;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Página para que el operador complete un formulario y lo envíe.
 * Solo accesible para usuarios con permiso submissions.create sobre
 * formularios activos con versión publicada.
 */
class FillForm extends EditRecord
{
    protected static string $resource = FormResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string
    {
        return 'Completar — '.$this->getRecord()->name;
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return $record->description ?? 'Completá los campos y enviá el formulario cuando estés listo.';
    }

    /**
     * Filament EditRecord por defecto verifica canEdit() → FormPolicy::update() → forms.update.
     * El operador no tiene ese permiso. Sobreescribimos para verificar submissions.create.
     */
    protected function authorizeAccess(): void
    {
        abort_unless(
            auth()->user()?->hasPermissionTo('submissions.create') ?? false,
            403,
            'No tenés permiso para completar formularios.'
        );
    }

    public function form(Schema $schema): Schema
    {
        /** @var Form $form */
        $form = $this->getRecord();
        $version = $form->currentVersion;

        // Sin versión publicada — no hay nada que completar
        if (! $version) {
            return $schema->schema([
                Section::make('Formulario sin publicar')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Placeholder::make('no_version')
                            ->label('')
                            ->content(new HtmlString(
                                '<p class="text-gray-500">Este formulario todavía no tiene una versión publicada.</p>'.
                                '<p class="text-gray-400 text-sm mt-1">Esperá a que el administrador lo publique antes de completarlo.</p>'
                            )),
                    ]),
            ]);
        }

        $fields = $form->fields()
            ->where('is_active', true)
            ->with('options')
            ->get();

        if ($fields->isEmpty()) {
            return $schema->schema([
                Section::make('Sin preguntas')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Placeholder::make('empty')
                            ->label('')
                            ->content(new HtmlString(
                                '<p class="text-gray-500">Este formulario no tiene preguntas activas configuradas.</p>'
                            )),
                    ]),
            ]);
        }

        $components = $fields->map(fn (FormField $field) => $this->buildComponent($field))->all();

        return $schema->schema([
            Section::make($form->name)
                ->description($form->description ?? 'Completá todos los campos obligatorios.')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema($components)
                ->columns(1),
        ]);
    }

    /**
     * En lugar de guardar el modelo Form, guardamos un Submission
     * con las respuestas del usuario autenticado.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Form $record */
        $version = $record->currentVersion;

        if (! $version) {
            Notification::make()
                ->warning()
                ->title('Sin versión publicada')
                ->body('No se puede enviar el formulario porque no tiene versión publicada.')
                ->send();

            return $record;
        }

        /** @var User $user */
        $user = auth()->user();

        $submission = Submission::create([
            'form_version_id' => $version->getKey(),
            'user_id' => $user->getKey(),
            'idempotency_key' => (string) Str::ulid(),
            'latitude' => 0,
            'longitude' => 0,
            'status' => SubmissionStatus::Complete,
            'submitted_at' => now(),
        ]);

        // Guardar cada respuesta individual
        foreach ($data as $fieldName => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $field = $record->fields()->where('name', $fieldName)->first();

            if (! $field) {
                continue;
            }

            // Procesar archivos: mover de livewire-tmp al media library
            if ($field->type === FormFieldType::File) {
                $value = $this->processFileUpload($value, $submission, $field);
            }

            SubmissionResponse::create([
                'submission_id' => $submission->getKey(),
                'field_name' => $fieldName,
                'field_type' => $field->type->value,
                'value' => is_array($value) ? json_encode($value) : (string) $value,
            ]);
        }

        Notification::make()
            ->success()
            ->title('Formulario enviado')
            ->body('Tus respuestas fueron registradas correctamente.')
            ->send();

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        // Manejamos la notificación en handleRecordUpdate — no duplicar
        return null;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Cancelar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('index')),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Enviar formulario')
            ->icon('heroicon-o-paper-airplane')
            ->color('primary');
    }

    /**
     * Procesa archivos subidos vía FileUpload de Livewire.
     * Mueve los archivos de livewire-tmp al media library de la submission.
     *
     * @param  mixed  $value  Path temporal o array de paths
     * @return string Nombre del archivo guardado (o JSON array si es múltiple)
     */
    private function processFileUpload(mixed $value, Submission $submission, FormField $field): string
    {
        $settings = $field->settings ?? [];
        $isMultiple = $settings['multiple_files'] ?? false;

        if ($isMultiple && is_array($value)) {
            $fileNames = [];
            foreach ($value as $filePath) {
                $fileName = $this->storeFileToMedia($filePath, $submission);
                if ($fileName) {
                    $fileNames[] = $fileName;
                }
            }

            return json_encode($fileNames);
        }

        // Single file
        $fileName = $this->storeFileToMedia($value, $submission);

        return $fileName ?: (string) $value;
    }

    /**
     * Mueve un archivo desde livewire-tmp al media library.
     */
    private function storeFileToMedia(string $filePath, Submission $submission): ?string
    {
        // El path viene como "livewire-tmp/filename.jpg" desde Livewire
        if (! Storage::disk('local')->exists($filePath)) {
            return null;
        }

        $fullPath = Storage::disk('local')->path($filePath);
        $originalName = pathinfo($filePath, PATHINFO_BASENAME);

        // Agregar al media library
        $media = $submission
            ->addMedia($fullPath)
            ->usingFileName($originalName)
            ->toMediaCollection('submissions');

        return $media->file_name;
    }

    private function buildComponent(FormField $field): mixed
    {
        $options = $field->options
            ->where('is_active', true)
            ->pluck('label', 'value')
            ->all();

        $settings = $field->settings ?? [];

        $component = match ($field->type) {
            FormFieldType::Text => TextInput::make($field->name)
                ->label($field->label)
                ->maxLength(($settings['max_length'] ?? null) ? (int) $settings['max_length'] : null)
                ->placeholder($settings['placeholder'] ?? ''),

            FormFieldType::Textarea => Textarea::make($field->name)
                ->label($field->label)
                ->rows(($settings['rows'] ?? null) ? (int) $settings['rows'] : 4)
                ->maxLength(($settings['max_length'] ?? null) ? (int) $settings['max_length'] : null)
                ->placeholder($settings['placeholder'] ?? ''),

            FormFieldType::Number => TextInput::make($field->name)
                ->label($field->label)
                ->type('number')
                ->minValue(($settings['min_value'] ?? null) ? (int) $settings['min_value'] : null)
                ->maxValue(($settings['max_value'] ?? null) ? (int) $settings['max_value'] : null)
                ->step(($settings['step'] ?? null) ? (string) $settings['step'] : null)
                ->suffix($settings['unit'] ?? null),

            FormFieldType::Select => Select::make($field->name)
                ->label($field->label)
                ->options($options)
                ->native(false)
                ->placeholder($settings['placeholder'] ?? 'Seleccioná una opción...')
                ->searchable($settings['searchable'] ?? false),

            FormFieldType::Radio => Radio::make($field->name)
                ->label($field->label)
                ->options($options)
                ->inline($settings['inline'] ?? false),

            FormFieldType::Checkbox => CheckboxList::make($field->name)
                ->label($field->label)
                ->options($options)
                ->columns($settings['inline'] ?? false ? 2 : 1)
                ->gridDirection($settings['inline'] ?? false ? GridDirection::Row : GridDirection::Column),

            FormFieldType::Date => DatePicker::make($field->name)
                ->label($field->label)
                ->native(false)
                ->placeholder($settings['placeholder'] ?? 'Seleccioná una fecha...')
                ->minDate(($settings['min_date'] ?? null) ? $settings['min_date'] : null)
                ->maxDate(($settings['max_date'] ?? null) ? $settings['max_date'] : null),

            FormFieldType::Time => TimePicker::make($field->name)
                ->label($field->label)
                ->native(false),

            FormFieldType::File => tap(
                FileUpload::make($field->name)
                    ->label($field->label)
                    ->multiple($settings['multiple_files'] ?? false),
                function (FileUpload $component) use ($settings): void {
                    // Filament genera siempre "mimetypes:{types}" y "max:{size}"
                    // aunque pasemos null/[]. Solo invocamos los modifiers
                    // cuando hay valores reales para evitar reglas inválidas
                    // que rompen Brick\Math con NumberFormatException.
                    if (! empty($settings['accepted_file_types'])) {
                        $component->acceptedFileTypes($settings['accepted_file_types']);
                    }

                    if (! empty($settings['max_file_size'])) {
                        $component->maxSize((int) $settings['max_file_size']);
                    }
                }
            ),
        };

        if ($field->is_required) {
            $component->required();
        }

        return $component;
    }
}
