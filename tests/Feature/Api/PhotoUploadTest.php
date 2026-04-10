<?php

declare(strict_types=1);

use App\Enums\FormFieldType;
use App\Enums\SubmissionStatus;
use App\Jobs\ProcessPhotoJob;
use App\Models\Central\Tenant;
use App\Models\Tenant\Form;
use App\Models\Tenant\FormField;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;
use App\Services\FormVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(fn () => dropCurrentTestTenantDatabases());

function createPhotoUploadFixture(Tenant $tenant): array
{
    return $tenant->run(function () {
        $role = Role::findOrCreate('operator', 'web');

        $user = User::query()->create([
            'name' => 'Photo User',
            'email' => 'photo@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $user->assignRole($role);

        $form = Form::query()->create([
            'name' => 'Photo Form',
            'description' => null,
            'is_active' => true,
        ]);

        FormField::query()->create([
            'form_id' => $form->id,
            'type' => FormFieldType::Text,
            'label' => 'Nota',
            'name' => 'nota',
            'is_required' => true,
            'validation_rules' => [],
            'order' => 1,
            'is_active' => true,
        ]);

        $version = app(FormVersionService::class)->publish($form);

        $submission = Submission::query()->create([
            'form_version_id' => $version->id,
            'user_id' => $user->id,
            'idempotency_key' => 'photo-key',
            'latitude' => -12.046374,
            'longitude' => -77.042793,
            'status' => SubmissionStatus::PendingPhotos,
            'submitted_at' => now(),
        ]);

        return [
            'token' => $user->createToken('mobile')->plainTextToken,
            'submission_id' => (string) $submission->id,
        ];
    });
}

it('returns 202 and queues photo processing', function () {
    Storage::fake('local');
    Queue::fake();

    $tenant = Tenant::create([
        'id' => 'photo-a',
        'name' => 'Photo A',
        'slug' => 'photo-a',
    ]);

    $fixture = createPhotoUploadFixture($tenant);

    $this->withToken($fixture['token'])
        ->post("/api/photo-a/submissions/{$fixture['submission_id']}/photos", [
            'photo' => UploadedFile::fake()->image('evidence.jpg'),
        ])
        ->assertStatus(202);

    Queue::assertPushed(ProcessPhotoJob::class);
});

it('marks the submission complete after processing the uploaded photo', function () {
    Storage::fake('local');

    $tenant = Tenant::create([
        'id' => 'photo-b',
        'name' => 'Photo B',
        'slug' => 'photo-b',
    ]);

    $fixture = createPhotoUploadFixture($tenant);

    $this->withToken($fixture['token'])
        ->post("/api/photo-b/submissions/{$fixture['submission_id']}/photos", [
            'photo' => UploadedFile::fake()->image('evidence.jpg'),
        ])
        ->assertStatus(202);

    $tenant->run(function () use ($fixture) {
        $submission = Submission::query()->findOrFail($fixture['submission_id']);

        expect($submission->fresh()->status)->toBe(SubmissionStatus::Complete)
            ->and($submission->getMedia('submissions'))->toHaveCount(1);
    });
});
