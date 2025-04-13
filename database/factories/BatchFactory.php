<?php

namespace Database\Factories;

use App\Enum\EncounterDateType;
use App\Models\Insurer;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'insurer_id' => Insurer::factory(),
            'processing_date' => $this->faker->unique()->date(),
            'batch_identifier' => $this->faker->word,
            'claim_count' => 0,
            'preferred_date_type' => EncounterDateType::SUBMISSION_DATE->value,
            'total_value' => $this->faker->randomFloat(2, 1000, 100000),
        ];
    }
}
