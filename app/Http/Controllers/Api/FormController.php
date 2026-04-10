<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\TenantRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\FormResource;
use App\Models\Tenant\Form;
use App\Models\Tenant\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FormController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = request()->user();

        $query = Form::query()
            ->where('is_active', true)
            ->whereNotNull('current_version_id')
            ->with('currentVersion')
            ->orderBy('name');

        if ($user->hasRole(TenantRole::Operator->value)) {
            $query->whereHas('assignments', function ($q) use ($user): void {
                $q->where('user_id', $user->getKey())
                    ->whereNull('revoked_at');
            });
        }

        return FormResource::collection($query->get());
    }

    public function show(Form $form): FormResource
    {
        $form->load('currentVersion');

        return new FormResource($form);
    }
}
