<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\TenantPermissionCatalog;
use App\Models\Tenant\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Limpiar caché de permisos de Spatie (necesario al correr dentro de un tenant)
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Crear todos los permisos
        foreach (TenantPermissionCatalog::permissions() as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // 3. Crear roles y asignarles sus permisos
        foreach (TenantPermissionCatalog::rolePermissions() as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }

        // 4. Crear usuario admin del tenant
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@tenant.test'],
            [
                'name'      => 'Admin Tenant',
                'password'  => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('admin');

        // 5. Crear usuario supervisor de prueba
        $supervisorUser = User::firstOrCreate(
            ['email' => 'supervisor@tenant.test'],
            [
                'name'      => 'Supervisor Test',
                'password'  => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $supervisorUser->assignRole('supervisor');

        // 6. Crear usuario operador de prueba
        $operatorUser = User::firstOrCreate(
            ['email' => 'operador@tenant.test'],
            [
                'name'      => 'Operador Test',
                'password'  => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $operatorUser->assignRole('operator');

        if ($this->command) {
            $this->command->info('  ✅ Roles y permisos creados: admin, supervisor, operator');
            $this->command->info('  ✅ Usuarios tenant: admin@tenant.test | supervisor@tenant.test | operador@tenant.test (password: password)');
        }
    }
}
