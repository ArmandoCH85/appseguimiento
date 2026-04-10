<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Form;
use App\Models\Tenant\FormField;
use App\Models\Tenant\FormVersion;
use Illuminate\Support\Facades\DB;

class FormVersionService
{
    public function publish(Form $form): FormVersion
    {
        return DB::transaction(function () use ($form): FormVersion {
            $form->load([
                'fields' => fn ($query) => $query->orderBy('order'),
                'fields.options' => fn ($query) => $query->orderBy('order'),
            ]);

            $nextVersion = ((int) $form->versions()->max('version_number')) + 1;

            $version = $form->versions()->create([
                'version_number' => $nextVersion,
                'schema_snapshot' => $this->buildSnapshot($form->fields),
                'published_at' => now(),
            ]);

            $form->forceFill([
                'current_version_id' => $version->id,
            ])->save();

            return $version->fresh();
        });
    }

    protected function buildSnapshot(iterable $fields): array
    {
        return collect($fields)
            ->map(function (FormField $field): array {
                return [
                    'id' => (string) $field->id,
                    'type' => $field->type->value,
                    'label' => $field->label,
                    'name' => $field->name,
                    'is_required' => $field->is_required,
                    'validation_rules' => $field->validation_rules ?? [],
                    'settings' => $field->settings ?? [],
                    'order' => $field->order,
                    'is_active' => $field->is_active,
                    'options' => $field->options
                        ->map(fn ($option): array => [
                            'id' => (string) $option->id,
                            'label' => $option->label,
                            'value' => $option->value,
                            'order' => $option->order,
                            'is_active' => $option->is_active,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }
}
