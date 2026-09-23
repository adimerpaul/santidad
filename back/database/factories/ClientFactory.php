<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "nombreRazonSocial" => $this->faker->name(),
            "codigoTipoDocumentoIdentidad" => "1",
            "numeroDocumento" => $this->faker->randomNumber(7,true),
            "email" => $this->faker->unique()->safeEmail(),
            "telefono" => $this->faker->phoneNumber(),
            "clienteProveedor" => $this->faker->randomElement(["Cliente","Proveedor"])
        ];
    }
}
