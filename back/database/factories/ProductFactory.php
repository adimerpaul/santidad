<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name,
            'barra' => $this->faker->name,
            'cantidad' => $this->faker->randomNumber(2),
            'costo' => $this->faker->randomFloat(2, 20, 30),
            'precio' => $this->faker->randomFloat(2, 20, 30),
            'activo' => $this->faker->word,
            'imagen' => $this->faker->imageUrl(640, 480, 'product', true),
            'descripcion' => $this->faker->text,
            "category_id" => $this->faker->numberBetween(1, 12),
        ];
    }
}
