<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\FormAssignment;
use App\Models\Tenant\User;

class FormAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('assignments.view');
    }

    public function view(User $user, FormAssignment $assignment): bool
    {
        return $user->hasPermissionTo('assignments.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('assignments.manage');
    }

    public function update(User $user, FormAssignment $assignment): bool
    {
        return $user->hasPermissionTo('assignments.manage');
    }

    public function delete(User $user, FormAssignment $assignment): bool
    {
        return $user->hasPermissionTo('assignments.manage');
    }
}
