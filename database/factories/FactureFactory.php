<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Facture>
 */
class FactureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->faker->unique()->numberBetween(1000, 9999),
            'invoice_name' => $this->faker->word,
            'organization' => $this->faker->company,
            'department' => $this->faker->word,
            'billing_date' => $this->faker->dateTimeThisMonth(),
            'consumption_period' => $this->faker->word,
            'currency' => $this->faker->currencyCode,
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'invoice_file_path' => $this->faker->filePath,
            'reception_date' => $this->faker->dateTimeThisMonth(),
            'fournisseur_id' => \App\Models\User::factory()->create(['role_id' => 3])->id,
        ];
    }
}
