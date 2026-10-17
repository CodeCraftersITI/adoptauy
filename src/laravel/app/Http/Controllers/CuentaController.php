<?php

namespace App\Http\Controllers;

/**
 * Panel "Mi cuenta" del Particular y del Refugio (RF:0022: a donde van después de ingresar).
 * Las secciones de publicaciones, solicitudes y seguimiento se agregan en los próximos pasos.
 */
class CuentaController extends Controller
{
    // GET /cuenta (protegida con el middleware rol:Particular,Refugio)
    public function index()
    {
        return view('cuenta.index', [
            'nombre' => session('nombre'),
            'tipo' => session('tipo'),
        ]);
    }
}
