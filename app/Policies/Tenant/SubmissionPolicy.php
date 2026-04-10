<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Submission;
use App\Models\Tenant\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('submissions.view');
    }

    public function view(User $user, Submission $submission): bool
    {
        if (! $user->hasPermissionTo('submissions.view')) {
            return false;
        }

        if ($user->hasPermissionTo('users.manage')) {
            return true;
        }

        return $submission->user_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('submissions.create');
    }
}
