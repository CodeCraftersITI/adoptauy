<?php

namespace App\Console\Commands;

use App\Models\PublicacionPerdida;
use Illuminate\Console\Command;

/**
 * Tarea diaria de las 03:00 (RF:0047, D71, CP-14): pasa a Archivada cada mascota
 * perdida o encontrada Vigente que llegó a su fecha de vencimiento sin renovarse.
 * Se programa en routes/console.php y la corre el contenedor scheduler.
 * A mano: docker compose exec app php artisan publicaciones:archivar-vencidas
 */
class ArchivarPerdidasVencidas extends Command
{
    protected $signature = 'publicaciones:archivar-vencidas';

    protected $description = 'Archiva las mascotas perdidas y encontradas vencidas (RF:0047)';

    public function handle()
    {
        // Vence el día que marca Fecha_Vencimiento: ese día ya se archiva (día 30, no el 29)
        $cantidad = PublicacionPerdida::where('Estado_Perdida', 'Vigente')
            ->where('Fecha_Vencimiento', '<=', now()->toDateString())
            ->update(['Estado_Perdida' => 'Archivada']);

        $this->info('Publicaciones archivadas por vencimiento: ' . $cantidad);
    }
}
