<?php

namespace App\Http\Controllers;

/**
 * Página "Sobre nosotros" (pantalla del prototipo del sitio público).
 * Por pedido del docente no muestra al equipo: "Realizado por CodeCrafters" va en el pie.
 */
class NosotrosController extends Controller
{
    // GET /nosotros
    public function index()
    {
        return view('nosotros');
    }
}
