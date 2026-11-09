<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * Usuarios ficticios para pruebas y datos de desarrollo (Ley 18.331: nunca datos reales).
 * Normalmente se usa a través de Particular::factory(), Refugio::factory() o
 * Administrador::factory(), que crean el Usuario junto con su subtipo.
 */
class UsuarioFactory extends Factory
{
    // Contraseña de todas las cuentas de prueba (sin ñ ni tildes a propósito)
    public const CONTRASEÑA = 'adoptauy-prueba';

    public function definition(): array
    {
        return [
            'Email' => fake()->unique()->userName() . '@prueba.uy',
            'Contraseña' => Hash::make(self::CONTRASEÑA),
            'Tipo_Usuario' => 'Particular',
        ];
    }
}
