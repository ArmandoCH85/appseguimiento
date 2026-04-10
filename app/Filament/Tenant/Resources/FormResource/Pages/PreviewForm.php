<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\FormResource\Pages;

use App\Enums\FormFieldType;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormField;
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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\GridDirection;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class PreviewForm extends EditForm
{
    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string
    {
        return 'Vista previa — '.$this->getRecord()->name;
    }

    public function getSubheading(): ?string
    {
        $record = $this->getRecord();

        return $record->description ?? 'Vista del formulario tal como lo ve el operador en la app.';
    }

    public function form(Schema $schema): Schema
    {
        /** @var Form $form */
        $form = $this->getRecord();

        $fields = $form->fields()
            ->where('is_active', true)
            ->with('options')
            ->get();

        if ($fields->isEmpty()) {
            return $schema->schema([
                Section::make('Sin preguntas aún')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        Placeholder::make('empty')
                            ->label('')
                            ->content(new HtmlString(
                                '<p class="text-gray-500">Este formulario todavía no tiene preguntas configuradas.</p>'.
                                '<p class="text-gray-400 text-sm mt-1">Pasá al constructor para agregar los campos.</p>'
                            )),
                    ]),
            ]);
        }

        $components = $fields->map(fn (FormField $field) => $this->buildComponent($field))->all();

        return $schema->schema([
            Section::make('Formulario')
                ->description('Vista de solo lectura. Así ve el operador el formulario en la app móvil.')
                ->icon('heroicon-o-eye')
                ->schema($components)
                ->columns(1),
        ])->disabled();
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('builder')
                ->label('Ir al constructor')
                ->icon('heroicon-o-pencil-square')
                ->color('primary')
                ->url(fn (): string => static::getResource()::getUrl('builder', ['record' => $this->getRecord()])),
            Action::make('editDetails')
                ->label('Editar datos básicos')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn (): string => static::getResource()::getUrl('edit', ['record' => $this->getRecord()])),
        ];
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

            FormFieldType::File => FileUpload::make($field->name)
                ->label($field->label)
                ->acceptedFileTypes($settings['accepted_file_types'] ?? [])
                ->maxSize(($settings['max_file_size'] ?? null) ? (int) $settings['max_file_size'] : null)
                ->multiple($settings['multiple_files'] ?? false),
        };

        if ($field->is_required) {
            $component->required();
        }

        return $component;
    }
}
