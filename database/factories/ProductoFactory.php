<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Producto>
 */
class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->bothify('P-####')),
            'nombre' => fake()->words(3, true),
            'unidad_medida' => 'NIU',
            'precio_venta' => 118,
            'afectacion_igv' => '10',
            'stock' => 10,
            'stock_minimo' => 2,
        ];
    }
}
