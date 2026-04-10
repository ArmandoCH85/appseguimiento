<?php

declare(strict_types=1);

namespace App\Policies\Tenant;

use App\Models\Tenant\Form;
use App\Models\Tenant\User;

class FormPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('forms.view');
    }

    public function view(User $user, Form $form): bool
    {
        return $user->hasPermissionTo('forms.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('forms.create');
    }

    public function update(User $user, Form $form): bool
    {
        return $user->hasPermissionTo('forms.update');
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->hasPermissionTo('forms.delete');
    }

    public function publish(User $user, Form $form): bool
    {
        return $user->hasPermissionTo('forms.publish');
    }
}
