<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAnimal;
use App\Models\Usuario;

// Panel "Mi cuenta" del Particular y del Refugio (RF:0020, RF:0027)
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
            // Aviso si la cuenta está restringida por un seguimiento incumplido (RF:0058)
            'restringida' => Usuario::estaRestringido(session('id_usuario')),
        ]);
    }
}
