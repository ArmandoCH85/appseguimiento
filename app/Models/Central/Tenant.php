<?php

declare(strict_types=1);

namespace App\Models\Central;

use LogicException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use SoftDeletes {
        forceDelete as private softDeletesForceDelete;
    }
    use HasDatabase, HasDomains;

    protected $appends = [
        'primary_domain',
    ];

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'slug', 'deleted_at'];
    }

    public function getPrimaryDomainAttribute(): ?string
    {
        return $this->domains()->value('domain');
    }

    public function forceDelete()
    {
        throw new LogicException('Hard deletion is prohibited for tenants.');
    }
}
