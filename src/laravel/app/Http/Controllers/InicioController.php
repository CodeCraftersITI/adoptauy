<?php

namespace App\Http\Controllers;

/**
 * Página de inicio pública (RF:0040, RF:0048).
 * Cuando existan las publicaciones, acá se van a buscar con Eloquent
 * las últimas En adopción y Perdida para pasárselas a la vista.
 */
class InicioController extends Controller
{
    // GET /
    public function index()
    {
        return view('inicio');
    }
}
