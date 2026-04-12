<?php

declare(strict_types=1);

namespace App\Http\Resources\Api;

use App\Enums\TenantRole;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $isOperator = $user && $user->hasRole(TenantRole::Operator->value);

        $assignment = null;

        if ($isOperator) {
            $assignmentRelation = $this->resource->assignments()
                ->where('user_id', $user->getKey())
                ->whereNull('revoked_at')
                ->first();

            if ($assignmentRelation) {
                $assignment = [
                    'assigned_at' => $assignmentRelation->created_at?->toISOString(),
                    'status' => 'active',
                ];
            }
        }

        return [
            'id' => (string) $this->resource->getKey(),
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'is_active' => $this->resource->is_active,
            'current_version' => $this->resource->currentVersion ? [
                'id' => (string) $this->resource->currentVersion->getKey(),
                'version_number' => $this->resource->currentVersion->version_number,
                'published_at' => $this->resource->currentVersion->published_at?->toISOString(),
                'schema' => $this->resource->currentVersion->schema_snapshot,
            ] : null,
            'assignment' => $assignment,
        ];
    }
}
