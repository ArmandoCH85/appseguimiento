<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Central\CentralUser;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        CentralUser::firstOrCreate(
            ['email' => 'superadmin@appseguimiento.test'],
            [
                'name'           => 'Super Admin',
                'password'       => bcrypt('password'),
                'is_super_admin' => true,
            ]
        );

        $this->command->info('✅ Super admin creado: superadmin@appseguimiento.test / password');
    }
}
