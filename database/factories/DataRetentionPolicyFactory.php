<?php

namespace Database\Factories;

use App\Models\DataRetentionPolicy;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class DataRetentionPolicyFactory extends Factory
{
    protected $model = DataRetentionPolicy::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(3, true),
            'description' => $this->faker->sentence(),
            'table_name' => $this->faker->randomElement(['audit_logs', 'log_accesos_sistema', 'notificaciones']),
            'columns' => ['*'],
            'conditions' => ['created_at' => ['operator' => '<', 'value' => 'retention_period']],
            'retention_days' => $this->faker->randomElement([90, 180, 365, 730]),
            'retention_period' => $this->faker->randomElement(['3 months', '6 months', '1 year', '2 years']),
            'action_on_expiry' => $this->faker->randomElement(['delete', 'anonymize', 'archive']),
            'archive_location' => $this->faker->optional()->word(),
            'anonymize_columns' => [],
            'anonymize_strategy' => $this->faker->optional()->randomElement(['hash', 'mask', 'null']),
            'enabled' => $this->faker->boolean(80),
            'auto_execute' => $this->faker->boolean(50),
            'cron_expression' => '0 2 * * 0',
            'rows_affected' => 0,
            'metadata' => [],
            'created_by' => Usuario::factory(),
        ];
    }
}
