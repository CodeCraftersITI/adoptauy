<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAdopcion;
use App\Models\PublicacionPerdida;

// Página de inicio pública (RF:0040, RF:0048, RF:0065)
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
