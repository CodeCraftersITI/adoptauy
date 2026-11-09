<?php

namespace Database\Factories;

use App\Models\Usuario;
use App\Rules\CedulaUruguaya;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Particular ficticio con su Usuario. Uso: Particular::factory()->create()
 */
class ParticularFactory extends Factory
{
    public function definition(): array
    {
        // Cédula ficticia de 8 dígitos con su dígito verificador correcto
        $base = (string) fake()->unique()->numberBetween(1000000, 6999999);

        return [
            'ID_Usuario' => Usuario::factory()->state(['Tipo_Usuario' => 'Particular']),
            'CI' => $base . CedulaUruguaya::digitoVerificador($base),
            'Nombre' => fake()->firstName(),
            'Apellido' => fake()->lastName(),
            'Telefono' => '09' . fake()->numerify('#######'),
        ];
    }
}
