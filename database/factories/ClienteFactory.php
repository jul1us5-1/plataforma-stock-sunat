<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Cliente>
 */
class ClienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tipo_documento' => '1',
            'numero_documento' => fake()->unique()->numerify('########'),
            'razon_social' => fake()->name(),
        ];
    }

    public function conRuc(): static
    {
        return $this->state(fn () => [
            'tipo_documento' => '6',
            'numero_documento' => fake()->unique()->numerify('20#########'),
            'razon_social' => fake()->company().' S.A.C.',
        ]);
    }
}
