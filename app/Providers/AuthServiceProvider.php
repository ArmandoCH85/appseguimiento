<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Tenant\Form;
use App\Models\Tenant\FormAssignment;
use App\Models\Tenant\Submission;
use App\Models\Tenant\User;
use App\Policies\Tenant\FormAssignmentPolicy;
use App\Policies\Tenant\FormPolicy;
use App\Policies\Tenant\SubmissionPolicy;
use App\Policies\Tenant\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Form::class => FormPolicy::class,
        FormAssignment::class => FormAssignmentPolicy::class,
        Submission::class => SubmissionPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
