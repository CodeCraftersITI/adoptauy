<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Refugio ficticio con su Usuario. Uso: Refugio::factory()->validado()->create()
 * El nombre dice "de prueba" para que nunca se confunda con un refugio real.
 */
class RefugioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ID_Usuario' => Usuario::factory()->state(['Tipo_Usuario' => 'Refugio']),
            'Nombre_Refugio' => 'Refugio de prueba ' . fake()->unique()->numberBetween(1, 999),
            'Certificado_Personeria' => 'documentos/prueba/certificado.pdf',
            'Comprobante_Domicilio' => 'documentos/prueba/comprobante.pdf',
            'Ubicacion' => 'Montevideo',
            'Estado_Validacion' => 'Pendiente de validación',
        ];
    }

    // Refugio ya aprobado por el Administrador
    public function validado()
    {
        return $this->state([
            'Estado_Validacion' => 'Validado',
            'Fecha_Solicitud' => now()->subDays(2),
            'Fecha_Resolucion' => now()->subDay(),
        ]);
    }
}
