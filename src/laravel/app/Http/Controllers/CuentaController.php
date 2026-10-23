<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAnimal;

/**
 * Panel "Mi cuenta" del Particular y del Refugio.
 * Muestra sus publicaciones de los dos tipos con su estado (RF:0020, RF:0027).
 * Las solicitudes y el seguimiento se agregan en los próximos sprints.
 */
class CuentaController extends Controller
{
    // GET /cuenta (protegida con el middleware rol:Particular,Refugio)
    public function index()
    {
        // Solo las publicaciones del usuario que inició sesión, la más nueva primero
        $publicaciones = PublicacionAnimal::where('ID_Usuario', session('id_usuario'))
            ->orderBy('Fecha_Creacion', 'desc')
            ->get();

        return view('cuenta.index', [
            'nombre' => session('nombre'),
            'tipo' => session('tipo'),
            'publicaciones' => $publicaciones,
        ]);
    }
}
