<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'auditable_type' => User::class,
            'auditable_id' => User::factory(),
            'event' => $this->faker->randomElement([
                'auth.login',
                'auth.logout',
                'auth.password.reset',
            ]),
            'properties' => [
                'ip' => $this->faker->ipv4(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'created_at' => now()->subMinutes($this->faker->numberBetween(0, 120)),
        ];
    }
}
