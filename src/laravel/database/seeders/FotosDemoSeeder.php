<?php

namespace Database\Seeders;

use App\Models\FotoAnimal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Reemplaza las fotos de los datos de demostración (archivos demo-*.jpg) por fotos nuevas
 * de perros y gatos según la especie de cada publicación. No toca la base: solo los archivos.
 * Sirve si los datos de demo se cargaron antes, con las fotos de colores.
 *   docker compose exec app php artisan db:seed --class=FotosDemoSeeder
 */
class FotosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $fotos = FotoAnimal::where('URL_Foto', 'like', 'fotos-animales/demo-%')->get();

        foreach ($fotos as $foto)
        {
            $especie = $foto->publicacion->Especie;
            Storage::disk('public')->put($foto->URL_Foto, FotosDePrueba::jpg($especie));
        }

        $this->command->info('Fotos de demostración reemplazadas: ' . $fotos->count());
    }
}
