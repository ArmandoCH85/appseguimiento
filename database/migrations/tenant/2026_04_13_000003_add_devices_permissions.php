<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'devices.view', 'guard_name' => 'web'],
            ['name' => 'devices.manage', 'guard_name' => 'web'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        // Asignar permisos solo si los roles ya existen.
        $adminRole = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first();

        if ($adminRole) {
            $adminRole->givePermissionTo('devices.view', 'devices.manage');
        }

        $supervisorRole = Role::query()
            ->where('name', 'supervisor')
            ->where('guard_name', 'web')
            ->first();

        if ($supervisorRole) {
            $supervisorRole->givePermissionTo('devices.view');
        }
    }

    public function down(): void
    {
        Permission::where('name', 'devices.view')->delete();
        Permission::where('name', 'devices.manage')->delete();
    }
};
