<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $directions = ['financiéres', 'assurance', 'fiscalité'];
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('amine123'),
            'phone' => fake()->phoneNumber(),
            'image' => fake()->image(),
            'isActive' => fake()->boolean(),
            'code_2FA' => fake()->numberBetween(000000, 999999),
            'isEnable' => fake()->boolean(),
            'idErp' => fake()->numberBetween(00000000, 99999999),
            'idFiscale' => fake()->numberBetween(00000000, 99999999),
            'adress' => fake()->address(),
            'nationnalites' => fake()->country(),
            'direction' => fake()->randomElement($directions),
            'role_id' => function () {
                return Role::factory()->create()->id;
            },
            'remember_token' => Str::random(10),
        ];
    }

    /*      $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone');
            $table->string('image');
            $table->boolean('isActive');
            $table->integer('idErp');
            $table->integer('idFiscale');
            $table->string('adress');
            $table->string('nationnalites');
            $table->string('direction');
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->rememberToken();
            $table->timestamps();
            
            $table->unsignedBigInteger('code_2FA')->nullable();
            $table->boolean('isEnable')->nullable();*/


    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
