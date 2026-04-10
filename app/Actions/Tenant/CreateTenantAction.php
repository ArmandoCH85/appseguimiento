<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Central\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Support\TenantPermissionCatalog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CreateTenantAction
{
    public function execute(array $data): Tenant
    {
        $validated = Validator::make($data, [
            'name'           => ['required', 'string', 'max:255'],
            'slug'           => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('tenants', 'slug')],
            'primary_domain' => ['required', 'string', 'max:255', Rule::unique('domains', 'domain')],
            'admin_email'    => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8'],
        ])->validate();

        // NOTE: No usamos DB::transaction() aquí porque MySQL ejecuta CREATE DATABASE
        // como un DDL que genera un implicit commit, invalidando cualquier transacción
        // activa en Laravel → "There is no active transaction".
        // La validación unique de slug y domain garantizan consistencia antes de crear.
        $tenant = Tenant::query()->create([
            'id'   => $validated['slug'],
            'name' => $validated['name'],
            'slug' => $validated['slug'],
        ]);

        $tenant->domains()->create([
            'domain' => $validated['primary_domain'],
        ]);

        $tenant->refresh();

        // Provisionar el admin inicial dentro del contexto del tenant
        $this->provisionAdminUser(
            tenant: $tenant,
            email: $validated['admin_email'],
            password: $validated['admin_password'],
        );

        return $tenant->load('domains');
    }

    private function provisionAdminUser(Tenant $tenant, string $email, string $password): void
    {
        $tenant->run(function () use ($email, $password): void {
            // Resetear el caché de permisos de Spatie dentro del contexto del tenant
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            // Crear todos los permisos y roles si aún no existen en este tenant
            foreach (TenantPermissionCatalog::rolePermissions() as $roleName => $permissions) {
                foreach ($permissions as $permissionName) {
                    Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
                }

                $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
                $role->syncPermissions($permissions);
            }

            // Crear el usuario admin inicial
            $adminUser = TenantUser::firstOrCreate(
                ['email' => $email],
                [
                    'name'      => 'Admin',
                    'password'  => bcrypt($password),
                    'is_active' => true,
                ]
            );

            $adminUser->assignRole('admin');
        });
    }
}
