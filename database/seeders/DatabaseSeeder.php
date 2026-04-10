<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Orden: super admin central → tenant demo (con roles, permisos y usuarios internos).
     */
    public function run(): void
    {
        $this->command->info('🌱 Iniciando seeders de desarrollo...');
        $this->command->newLine();

        $this->call([
            SuperAdminSeeder::class,
            TenantSeeder::class,
        ]);

        $this->command->newLine();
        $this->command->info('🎉 Seeders completados.');
        $this->command->newLine();
        $this->command->line('  Central:');
        $this->command->line('    superadmin@appseguimiento.test / password');
        $this->command->newLine();
        $this->command->line('  Tenant demo (dominio: demo.localhost):');
        $this->command->line('    admin@tenant.test / password       → rol: admin');
        $this->command->line('    supervisor@tenant.test / password  → rol: supervisor');
        $this->command->line('    operador@tenant.test / password    → rol: operator');
    }
}
