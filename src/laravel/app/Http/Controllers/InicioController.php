<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAdopcion;
use App\Models\PublicacionPerdida;

/**
 * Página de inicio pública (RF:0040, RF:0048, CP-08).
 * Muestra por separado las últimas mascotas En adopción y las Perdida vigentes.
 * Solo se listan publicaciones visibles: una publicación En revisión o Suspendida
 * por reportes no aparece (13.2.5).
 */
class InicioController extends Controller
{
    // GET /
    public function index()
    {
        // whereHas filtra por un dato de la tabla relacionada (Publicacion_Animal)
        $adopciones = PublicacionAdopcion::where('Estado_Adopcion', 'En adopción')
            ->whereHas('publicacion', function ($consulta)
            {
                $consulta->where('Estado_Moderacion', 'Visible');
            })
            ->orderBy('ID_Publicacion', 'desc')
            ->take(8)
            ->get();

        // Perdidas: además, que no estén vencidas (vigencia de 30 días, RF:0047)
        $perdidas = PublicacionPerdida::where('Estado_Perdida', 'Perdida')
            ->where('Fecha_Vencimiento', '>=', now()->toDateString())
            ->whereHas('publicacion', function ($consulta)
            {
                $consulta->where('Estado_Moderacion', 'Visible');
            })
            ->orderBy('ID_Publicacion', 'desc')
            ->take(6)
            ->get();

        return view('inicio', [
            'adopciones' => $adopciones,
            'perdidas' => $perdidas,
        ]);
    }
}
