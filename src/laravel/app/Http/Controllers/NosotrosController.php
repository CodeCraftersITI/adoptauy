<?php

namespace App\Http\Controllers;

/**
 * Página "Sobre nosotros" (pantalla del prototipo del sitio público).
 * Le pasa a la vista la lista del equipo, como el ejemplo de view() con datos del teórico.
 */
class NosotrosController extends Controller
{
    // GET /nosotros
    public function index()
    {
        $equipo = [
            ['nombre' => 'Camilo Blanco', 'rol' => 'Coordinador · Base de datos'],
            ['nombre' => 'Leandro Estévez', 'rol' => 'Subcoordinador'],
            ['nombre' => 'Iñaki Pérez', 'rol' => 'Integrante'],
            ['nombre' => 'Rodrigo González', 'rol' => 'Integrante'],
        ];

        return view('nosotros', ['equipo' => $equipo]);
    }
}
