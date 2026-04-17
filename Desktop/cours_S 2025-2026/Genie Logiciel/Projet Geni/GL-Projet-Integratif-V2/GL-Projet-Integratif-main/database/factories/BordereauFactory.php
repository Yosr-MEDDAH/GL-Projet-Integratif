<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bordereau>
 */
class BordereauFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date_sent' => $this->faker->dateTimeThisMonth(),
            'folder' => $this->faker->word,
            'status' => $this->faker->randomElement(['En cours', 'Archivé']),
            'nature' => $this->faker->word,
            'reference' => $this->faker->word,
        ];
    }
}
