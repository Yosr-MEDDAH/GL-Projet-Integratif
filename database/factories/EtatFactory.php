<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Etat>
 */
class EtatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_etat' => $this->faker->randomElement(['Attente', 'Envoyé', 'Accepté', 'Refusé', 'Cloturé']),
        ];
    }
}
