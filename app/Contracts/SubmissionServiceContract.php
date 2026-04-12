<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Tenant\FormVersion;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;

interface SubmissionServiceContract
{
    public function createOrRetrieve(FormVersion $version, User $user, array $data): Submission;

    public function validateResponses(FormVersion $version, array $responses): array;

    public function updateSubmission(Submission $submission, User $user, array $data): Submission;
}
