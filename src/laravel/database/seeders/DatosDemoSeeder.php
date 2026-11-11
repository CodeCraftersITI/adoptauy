<?php

namespace Database\Seeders;

use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de demostración: muchas publicaciones para ver cómo queda el sitio con datos.
 * Todo es ficticio (Ley 18.331): usuarios hechos con factories. Las fotos se bajan de internet
 * (ver FotosDePrueba), así que cargarlo tarda un poco; sin internet usa fotos de colores.
 *
 * Se carga a mano, NO con el db:seed común:
 *   docker compose exec app php artisan db:seed --class=DatosDemoSeeder
 * Para empezar de cero (borra TODA la base de desarrollo):
 *   docker compose exec app php artisan migrate:fresh --seed
 */
class DatosDemoSeeder extends Seeder
{
    // Cuántas publicaciones de cada tipo
    private const CANTIDAD = 20;

    private const RAZAS = [
        'Perro' => ['Mestizo', 'Labrador', 'Caniche', 'Ovejero alemán', 'Beagle', 'Golden', 'Cimarrón uruguayo'],
        'Gato' => ['Mestizo', 'Siamés', 'Persa', 'Común europeo', 'Angora'],
    ];

    private const BARRIOS = [
        'Pocitos', 'Malvín', 'Centro', 'Cordón', 'Parque Rodó', 'Buceo', 'La Blanqueada',
        'Prado', 'Carrasco', 'Unión', 'Sayago', 'Cerro', 'Ciudad Vieja', 'Tres Cruces',
    ];

    private const SALUD = [
        'Vacunas al día y desparasitado.',
        'Sano, con todas las vacunas.',
        'Vacunado. Necesita control de peso.',
        'Desparasitado, falta la última vacuna.',
    ];

    private const CARACTERISTICAS = [
        'Collar rojo con chapita.', 'Manchas blancas en el pecho.', 'Muy asustadizo, no se deja agarrar.',
        'Tiene una oreja caída.', 'Pelo largo, color marrón.', 'Cola corta.', 'Collar azul sin chapa.',
    ];

    private $numeroFoto = 0;

    public function run(): void
    {
        $publicantes = $this->crearPublicantes();

        for ($i = 0; $i < self::CANTIDAD; $i++)
        {
            $this->crearAdopcion($publicantes[array_rand($publicantes)]);
            $this->crearAviso($publicantes[array_rand($publicantes)], 'Perdida');
            $this->crearAviso($publicantes[array_rand($publicantes)], 'Encontrada');
        }

        $this->command->info('Datos de demostración cargados: ' . self::CANTIDAD . ' en adopción, '
            . self::CANTIDAD . ' perdidas y ' . self::CANTIDAD . ' encontradas.');
    }

    // 5 particulares y 2 refugios validados, todos ficticios, dados de alta hace 60 días
    private function crearPublicantes()
    {
        $hace60dias = now()->subDays(60);
        $publicantes = [];

        for ($i = 0; $i < 5; $i++)
        {
            $usuario = Usuario::factory()->create(['Fecha_Alta' => $hace60dias]);
            Particular::factory()->create(['ID_Usuario' => $usuario->ID_Usuario]);
            $publicantes[] = $usuario;
        }

        for ($i = 0; $i < 2; $i++)
        {
            $usuario = Usuario::factory()->create(['Fecha_Alta' => $hace60dias, 'Tipo_Usuario' => 'Refugio']);
            Refugio::factory()->validado()->create(['ID_Usuario' => $usuario->ID_Usuario]);
            $publicantes[] = $usuario;
        }

        return $publicantes;
    }

    // Datos comunes (Publicacion_Animal), con fecha de creación en los últimos 20 días
    private function crearPublicacion($usuario, $tipo)
    {
        $especie = fake()->randomElement(['Perro', 'Gato']);

        $publicacion = new PublicacionAnimal([
            'Especie' => $especie,
            'Raza' => fake()->randomElement(self::RAZAS[$especie]),
            'Sexo' => fake()->randomElement(['Macho', 'Hembra']),
        ]);
        $publicacion->ID_Usuario = $usuario->ID_Usuario;
        $publicacion->Tipo_Publicacion = $tipo;
        $publicacion->Fecha_Creacion = now()->subDays(rand(0, 20))->subHours(rand(0, 12));
        $publicacion->save();

        // Entre 1 y 3 fotos de prueba
        for ($f = 0; $f < rand(1, 3); $f++)
        {
            FotoAnimal::create([
                'ID_Publicacion' => $publicacion->ID_Publicacion,
                'URL_Foto' => $this->generarFoto($especie),
            ]);
        }

        return $publicacion;
    }

    private function crearAdopcion($usuario)
    {
        $publicacion = $this->crearPublicacion($usuario, 'Adopción');

        PublicacionAdopcion::create([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Edad_Meses' => rand(2, 120),
            'Tamaño' => fake()->randomElement(['Pequeño', 'Mediano', 'Grande']),
            'Estado_Salud' => fake()->randomElement(self::SALUD),
            'Estado_Esterilizacion' => fake()->randomElement(['Esterilizado', 'No esterilizado']),
            'Ubicacion_Aproximada' => fake()->randomElement(self::BARRIOS),
        ]);
    }

    private function crearAviso($usuario, $tipoAviso)
    {
        $publicacion = $this->crearPublicacion($usuario, 'Perdida');

        $aviso = new PublicacionPerdida([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Tipo_Aviso' => $tipoAviso,
            // Una encontrada a veces no tiene nombre (es opcional, RNE26)
            'Nombre_Mascota' => ($tipoAviso == 'Encontrada' && rand(0, 1)) ? null : fake()->firstName(),
            'Caracteristicas' => fake()->randomElement(self::CARACTERISTICAS),
            'Fecha_Suceso' => $publicacion->Fecha_Creacion->copy()->subDays(rand(0, 5))->toDateString(),
            'Zona_Suceso' => fake()->randomElement(self::BARRIOS),
            'Contacto' => '09' . fake()->numerify('#######'),
        ]);
        // Vence 30 días después de creada (RNE17): como se crearon en los últimos 20 días, siguen vigentes
        $aviso->Fecha_Vencimiento = $publicacion->Fecha_Creacion->copy()->addDays(30)->toDateString();
        $aviso->save();
    }

    // Baja una foto de un perro o un gato (FotosDePrueba) y la guarda en
    // storage/app/public/fotos-animales con el prefijo demo-
    private function generarFoto($especie)
    {
        $this->numeroFoto++;
        $ruta = 'fotos-animales/demo-' . uniqid() . '-' . $this->numeroFoto . '.jpg';
        Storage::disk('public')->put($ruta, FotosDePrueba::jpg($especie));

        return $ruta;
    }
}
