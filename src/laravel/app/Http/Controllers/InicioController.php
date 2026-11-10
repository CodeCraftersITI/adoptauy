<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAdopcion;
use App\Models\PublicacionPerdida;

/**
 * Página de inicio pública (RF:0040, RF:0048, RF:0065, CP-08, CP-48).
 * Muestra por separado las últimas mascotas En adopción, las perdidas vigentes y las encontradas vigentes.
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

        // Perdidas y encontradas, por separado: vigentes y no vencidas (RF:0047, RF:0048, RF:0065)
        $perdidas = PublicacionPerdida::vigentes(PublicacionPerdida::PERDIDA)
            ->orderBy('ID_Publicacion', 'desc')
            ->take(6)
            ->get();

        $encontradas = PublicacionPerdida::vigentes(PublicacionPerdida::ENCONTRADA)
            ->orderBy('ID_Publicacion', 'desc')
            ->take(6)
            ->get();

        return view('inicio', [
            'adopciones' => $adopciones,
            'perdidas' => $perdidas,
            'encontradas' => $encontradas,
        ]);
    }
}
