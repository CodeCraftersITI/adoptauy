<?php

namespace Database\Seeders;

use App\Models\Administrador;
use App\Models\Particular;
use App\Models\Refugio;
use App\Models\Usuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Seeder;

/**
 * Cuentas ficticias para desarrollo y para la demo del Hito 2 (login por rol, RF:0022).
 * Se cargan con: php artisan db:seed
 * Todas usan la contraseña de UsuarioFactory::CONTRASEÑA.
 *
 * El Administrador y el refugio Validado se cargan acá porque todavía no existe
 * la pantalla donde el Administrador valida refugios (RF:0005).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Administrador
        $admin = Usuario::factory()->create([
            'Email' => 'admin@adoptauy.test',
            'Tipo_Usuario' => 'Administrador',
        ]);
        Administrador::create(['ID_Usuario' => $admin->ID_Usuario]);

        // Refugio validado
        $refugio = Usuario::factory()->create([
            'Email' => 'refugio@adoptauy.test',
            'Tipo_Usuario' => 'Refugio',
        ]);
        Refugio::factory()->validado()->create([
            'ID_Usuario' => $refugio->ID_Usuario,
            'Nombre_Refugio' => 'Refugio de prueba',
        ]);

        // Particular
        $particular = Usuario::factory()->create([
            'Email' => 'particular@adoptauy.test',
        ]);
        Particular::factory()->create(['ID_Usuario' => $particular->ID_Usuario]);

        $this->command->info('Cuentas de prueba creadas. Contraseña: ' . UsuarioFactory::CONTRASEÑA);
    }
}
