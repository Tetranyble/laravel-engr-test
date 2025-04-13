<?php

namespace Database\Factories;

use App\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Claim>
 */
class ClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => \App\Models\Provider::factory(),
            'insurer_id' => \App\Models\Insurer::factory(),
            'specialty' => $this->faker->numberBetween(1, 10),
            'batch_id' => Batch::factory(),
            'encounter_date' => $this->faker->date(),
            'submission_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'priority_level' => $this->faker->numberBetween(1, 5),
            //'submission_weight' => $this->faker->randomFloat(2, 500, 5000),
            //'encounter_weight' => $this->faker->randomFloat(2, 500, 5000),
            'total_value' => $this->faker->randomFloat(2, 500, 5000),
        ];
    }
}
