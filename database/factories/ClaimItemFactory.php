<?php

namespace Database\Factories;

use App\Models\Claim;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClaimItem>
 */
class ClaimItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'claim_id' => Claim::factory(),
            'name' => $this->faker->word(),
            'unit_price' => $this->faker->randomFloat(2, 50, 1000),
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
