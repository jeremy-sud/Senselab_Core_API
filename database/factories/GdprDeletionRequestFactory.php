<?php

namespace Database\Factories;

use App\Models\GdprDeletionRequest;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class GdprDeletionRequestFactory extends Factory
{
    protected $model = GdprDeletionRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => Usuario::factory(),
            'email' => $this->faker->safeEmail(),
            'request_type' => $this->faker->randomElement(['deletion', 'anonymization', 'export']),
            'status' => $this->faker->randomElement(['pending', 'approved', 'completed', 'rejected']),
            'reason' => $this->faker->sentence(),
            'scope' => ['personal_data', 'activity_logs'],
            'data_summary' => [],
            'ip_address' => $this->faker->ipv4(),
            'action_log' => [],
            'gdpr_request_id' => $this->faker->unique()->uuid(),
            'verified_identity' => $this->faker->boolean(70),
            'verification_method' => $this->faker->randomElement(['email', 'id_document', 'phone']),
            'retry_count' => 0,
        ];
    }
}
