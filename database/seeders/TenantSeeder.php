<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Central\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // Crear tenant demo si no existe
        $tenant = Tenant::firstOrCreate(
            ['id' => 'demo'],
            [
                'name' => 'Demo Company',
                'slug' => 'demo',
            ]
        );

        // Asignar dominio si aún no tiene
        if ($tenant->domains()->count() === 0) {
            $tenant->domains()->create([
                'domain' => 'demo.localhost',
            ]);
        }

        $this->command->info('✅ Tenant demo creado con dominio: demo.localhost');

        // Inicializar el contexto del tenant y correr el seeder interno
        tenancy()->initialize($tenant);

        (new TenantDatabaseSeeder())->setCommand($this->command)->run();

        tenancy()->end();

        $this->command->info('✅ Seed de datos internos del tenant demo completado');
    }
}
