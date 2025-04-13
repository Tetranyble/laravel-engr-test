<?php

namespace Database\Factories;

use App\Enum\EncounterDateType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Insurer>
 */
class InsurerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $insurers = [
            ['name' => 'Insurer A', 'code' => 'INS-A'],
            ['name' => 'Insurer B', 'code' => 'INS-B'],
            ['name' => 'Insurer C', 'code' => 'INS-C'],
            ['name' => 'Insurer D', 'code' => 'INS-D'],
        ];

        $insurer = $this->faker->randomElement($insurers);

        return [
            'name' => $insurer['name'],
            'code' => $insurer['code'].$this->faker->numberBetween(1, 3000),
            'email' => $this->faker->unique()->safeEmail,
            'preferred_date_type' => $this->faker->randomElement([
                EncounterDateType::ENCOUNTER_DATE->value,
                EncounterDateType::SUBMISSION_DATE->value]),
            'specialty_multipliers' => [
                'cardiology' => 1.2,
                'neurology' => 1.3,
                'oncology' => 1.5,
            ],
            'priority_multipliers' => [
                '1' => 1.0,
                '3' => 1.25,
                '5' => 1.5,
            ],
            'daily_capacity' => $this->faker->randomFloat(2, 50, 500),
            'min_batch_size' => $this->faker->randomFloat(2, 5, 20),
            'max_batch_size' => $this->faker->randomFloat(2, 30, 100),
            'month_min_percent_limit' => $this->faker->randomFloat(2, 10, 30),
            'month_max_percent_limit' => $this->faker->randomFloat(2, 60, 100),
            'base_processing_cost' => $this->faker->randomFloat(2, 100, 1000),
        ];
    }

    public function insurer(string $name, string $code): static
    {
        return $this->state(fn () => [
            'name' => $name,
            'code' => $code,
        ]);
    }
}
