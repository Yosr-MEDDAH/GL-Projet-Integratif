<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reclamation>
 */
class ReclamationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence,
            'text' => $this->faker->paragraph,
            'path' => $this->faker->imageUrl(),
            'file' => $this->faker->word . '.pdf',
            'attached_file' => $this->faker->word . '.pdf',
            'fournisseur_id' => \App\Models\User::factory()->create(['role_id' => 3])->id,
        ];
    }
}
