<?php

namespace Database\Seeders;

use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Datos para la prueba de carga CP-38 (RNF:0001, RNF:0004 a RNF:0006): 1.000 publicaciones
 * ficticias (700 en adopción, 150 perdidas y 150 encontradas) y 15 cuentas, una por usuario
 * simulado: carga1@prueba.uy a carga15@prueba.uy, contraseña adoptauy-prueba.
 *
 * Solo corre en la base adoptauy_carga, para no llenar la de desarrollo:
 *   docker compose exec -e DB_DATABASE=adoptauy_carga app php artisan migrate:fresh --seed --seeder=CargaSeeder
 * Todo es inventado (Ley 18.331). Todas las publicaciones usan la misma foto, para que cargar
 * sea rápido y sin internet.
 */
class CargaSeeder extends Seeder
{
    private const PUBLICACIONES = 1000;
    private const USUARIOS = 15;

    private const BARRIOS = ['Pocitos', 'Malvín', 'Centro', 'Cordón', 'Prado', 'Buceo', 'Carrasco', 'Unión', 'Cerro', 'Sayago'];

    public function run(): void
    {
        if (DB::connection()->getDatabaseName() != 'adoptauy_carga')
        {
            $this->command->error('CargaSeeder solo corre en la base adoptauy_carga. No se cargó nada.');
            return;
        }

        // Una foto de un color, la misma para todas las publicaciones
        $foto = 'fotos-animales/carga.jpg';
        $imagen = imagecreatetruecolor(640, 480);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 176, 58, 105));
        ob_start();
        imagejpeg($imagen);
        Storage::disk('public')->put($foto, ob_get_clean());

        // Las 15 cuentas de los usuarios simulados, con el correo confirmado
        $usuarios = [];
        for ($i = 1; $i <= self::USUARIOS; $i++)
        {
            $usuario = Usuario::factory()->create(['Email' => 'carga' . $i . '@prueba.uy']);
            Particular::factory()->create(['ID_Usuario' => $usuario->ID_Usuario]);
            $usuarios[] = $usuario->ID_Usuario;
        }

        for ($n = 0; $n < self::PUBLICACIONES; $n++)
        {
            // 70 % en adopción, 15 % perdidas y 15 % encontradas
            $tipo = $n < 700 ? 'Adopción' : 'Perdida';
            $especie = fake()->randomElement(['Perro', 'Gato']);

            $publicacion = new PublicacionAnimal([
                'Especie' => $especie,
                'Raza' => fake()->randomElement(['Mestizo', 'Labrador', 'Siamés', 'Caniche', 'Persa']),
                'Sexo' => fake()->randomElement(['Macho', 'Hembra']),
            ]);
            $publicacion->ID_Usuario = $usuarios[array_rand($usuarios)];
            $publicacion->Tipo_Publicacion = $tipo;
            $publicacion->Fecha_Creacion = now()->subDays(rand(0, 25));
            $publicacion->save();

            if ($tipo == 'Adopción')
            {
                PublicacionAdopcion::create([
                    'ID_Publicacion' => $publicacion->ID_Publicacion,
                    'Edad_Meses' => rand(2, 120),
                    'Tamaño' => fake()->randomElement(['Pequeño', 'Mediano', 'Grande']),
                    'Estado_Salud' => 'Vacunas al día.',
                    'Estado_Esterilizacion' => fake()->randomElement(['Esterilizado', 'No esterilizado']),
                    'Ubicacion_Aproximada' => fake()->randomElement(self::BARRIOS),
                ]);
            }
            else
            {
                $aviso = new PublicacionPerdida([
                    'ID_Publicacion' => $publicacion->ID_Publicacion,
                    'Tipo_Aviso' => $n < 850 ? 'Perdida' : 'Encontrada',
                    'Nombre_Mascota' => fake()->firstName(),
                    'Caracteristicas' => 'Collar rojo.',
                    'Fecha_Suceso' => now()->subDays(rand(0, 5))->toDateString(),
                    'Zona_Suceso' => fake()->randomElement(self::BARRIOS),
                    'Contacto' => '09' . fake()->numerify('#######'),
                ]);
                $aviso->Fecha_Vencimiento = now()->addDays(rand(5, 30))->toDateString();
                $aviso->save();
            }

            FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => $foto]);
        }

        $this->command->info('Carga lista: ' . self::PUBLICACIONES . ' publicaciones y ' . self::USUARIOS . ' cuentas (carga1@prueba.uy ...).');
    }
}
