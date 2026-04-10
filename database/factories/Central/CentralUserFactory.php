<?php

declare(strict_types=1);

namespace Database\Factories\Central;

use App\Models\Central\CentralUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CentralUser>
 */
class CentralUserFactory extends Factory
{
    protected $model = CentralUser::class;

    public function definition(): array
    {
        return [
            'name'           => fake()->name(),
            'email'          => fake()->unique()->safeEmail(),
            'password'       => bcrypt('password'),
            'is_super_admin' => false,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => ['is_super_admin' => true]);
    }
}
