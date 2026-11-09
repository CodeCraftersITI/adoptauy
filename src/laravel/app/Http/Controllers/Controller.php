<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

abstract class Controller
{
    // Abre la sesión de un usuario y lo manda a su panel (RF:0022). La usan el login con
    // correo y contraseña (SesionController) y el de Google (GoogleController).
    protected function iniciarSesion(Request $request, Usuario $usuario)
    {
        // Nuevo identificador de sesión para evitar la fijación de sesión (session fixation)
        $request->session()->regenerate();

        // Guardamos en la sesión quién es y su rol; el middleware VerificarRol lee 'tipo'
        session([
            'id_usuario' => $usuario->ID_Usuario,
            'tipo' => $usuario->Tipo_Usuario,
            'nombre' => $usuario->nombreVisible(),
        ]);

        // Cada rol va a su panel
        if ($usuario->Tipo_Usuario == 'Administrador')
        {
            return redirect('/admin');
        }

        return redirect('/cuenta');
    }
}
