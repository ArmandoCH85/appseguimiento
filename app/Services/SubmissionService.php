<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\SubmissionServiceContract;
use App\Enums\SubmissionStatus;
use App\Models\Tenant\FormVersion;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubmissionService implements SubmissionServiceContract
{
    public function createOrRetrieve(FormVersion $version, User $user, array $data): Submission
    {
        $existing = Submission::query()
            ->with('responses')
            ->where('idempotency_key', $data['idempotency_key'])
            ->first();

        if ($existing) {
            return $existing;
        }

        $status = $data['status'] ?? SubmissionStatus::PendingPhotos->value;
        $isDraft = $status === SubmissionStatus::Draft->value;

        $responses = $isDraft
            ? $this->normalizeDraftResponses($version, $data['responses'] ?? [])
            : $this->validateResponses($version, $data['responses'] ?? []);

        return DB::transaction(function () use ($version, $user, $data, $responses, $status, $isDraft): Submission {
            $submission = Submission::query()->create([
                'form_version_id' => $version->getKey(),
                'user_id' => $user->getKey(),
                'idempotency_key' => $data['idempotency_key'],
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'status' => SubmissionStatus::from($status),
                'submitted_at' => $isDraft ? null : now(),
            ]);

            foreach ($responses as $response) {
                $submission->responses()->create($response);
            }

            return $submission->load('responses');
        });
    }

    public function validateResponses(FormVersion $version, array $responses): array
    {
        $snapshot = collect($version->schema_snapshot)
            ->keyBy('name');

        $unknownFields = collect(array_keys($responses))
            ->diff($snapshot->keys())
            ->values()
            ->all();

        if ($unknownFields !== []) {
            throw ValidationException::withMessages([
                'responses' => ['Unknown fields: '.implode(', ', $unknownFields)],
            ]);
        }

        $rules = [];

        foreach ($snapshot as $fieldName => $field) {
            $fieldRules = $field['validation_rules'] ?? [];

            if (($field['is_required'] ?? false) === true) {
                array_unshift($fieldRules, 'required');
            } else {
                array_unshift($fieldRules, 'nullable');
            }

            $type = $field['type'] ?? null;

            if ($type === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($type === 'checkbox') {
                $fieldRules[] = 'array';
            } elseif ($type === 'file') {
                // File uploads skip string validation
            } else {
                $fieldRules[] = 'string';
            }

            $rules[$fieldName] = $fieldRules;
        }

        $validated = Validator::make($responses, $rules)->validate();

        return $snapshot
            ->map(function (array $field, string $fieldName) use ($validated): array {
                return [
                    'field_name' => $fieldName,
                    'field_type' => $field['type'],
                    'value' => array_key_exists($fieldName, $validated)
                        ? $this->normalizeValue($validated[$fieldName])
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    protected function normalizeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
    }

    protected function normalizeDraftResponses(FormVersion $version, array $responses): array
    {
        $snapshot = collect($version->schema_snapshot)->keyBy('name');

        return collect($responses)
            ->map(function ($value, string $fieldName) use ($snapshot): array {
                return [
                    'field_name' => $fieldName,
                    'field_type' => $snapshot->get($fieldName, [])['type'] ?? 'text',
                    'value' => $this->normalizeValue($value),
                ];
            })
            ->values()
            ->all();
    }

    public function updateSubmission(Submission $submission, User $user, array $data): Submission
    {
        $validTransitions = [
            SubmissionStatus::Draft->value => [SubmissionStatus::PendingPhotos->value],
            SubmissionStatus::PendingPhotos->value => [SubmissionStatus::Draft->value],
        ];

        if (isset($data['status'])) {
            $newStatus = SubmissionStatus::from($data['status']);
            $allowed = $validTransitions[$submission->status->value] ?? [];

            if (! in_array($newStatus->value, $allowed, true)) {
                throw ValidationException::withMessages([
                    'status' => ['Transición de estado no permitida.'],
                ]);
            }

            $submission->status = $newStatus;

            if ($newStatus === SubmissionStatus::PendingPhotos && $submission->submitted_at === null) {
                $submission->submitted_at = now();
            }
        }

        if (array_key_exists('latitude', $data)) {
            $submission->latitude = $data['latitude'];
        }

        if (array_key_exists('longitude', $data)) {
            $submission->longitude = $data['longitude'];
        }

        if (array_key_exists('responses', $data) && is_array($data['responses'])) {
            $submission->responses()->delete();

            $version = $submission->formVersion;
            $responses = $this->validateResponses($version, $data['responses']);

            foreach ($responses as $response) {
                $submission->responses()->create($response);
            }
        }

        $submission->save();

        return $submission->load('responses');
    }
}
