<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Central\Tenant;

/**
 * Post-creation seeding for a tenant:
 * - Creates the default admin user
 * - Seeds default roles (admin, supervisor, operator)
 *
 * NOTE: This action runs INSIDE the tenant context (after DB provisioning).
 * It is invoked by wiring it into the TenancyServiceProvider pipeline (Phase 2).
 */
class ProvisionTenantAction
{
    public function execute(Tenant $tenant): void
    {
        $tenant->run(function () use ($tenant) {
            // Default roles will be seeded here in Phase 2
            // when spatie/permission tenant migrations exist.
            // Placeholder — actual seeding implemented in Phase 2.
        });
    }
}
