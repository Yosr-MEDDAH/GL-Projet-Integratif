<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BonDeCommande>
 */
class BonDeCommandeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by' => $this->faker->name,
            'delai_paiement' => $this->faker->randomElement(['30 jours', '45 jours', '60 jours']),
            'idErp' => $this->faker->numberBetween(1, 100),
            'num_commande' => $this->faker->unique()->numerify('CMD#####'),
        ];
    }
}
