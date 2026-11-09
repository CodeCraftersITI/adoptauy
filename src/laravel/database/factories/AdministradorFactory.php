<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Administrador ficticio con su Usuario. Uso: Administrador::factory()->create()
 */
class AdministradorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ID_Usuario' => Usuario::factory()->state(['Tipo_Usuario' => 'Administrador']),
        ];
    }
}
