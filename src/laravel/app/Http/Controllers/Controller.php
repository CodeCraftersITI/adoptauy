<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ConexionPorRol;
use App\Mail\ConfirmarCorreo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

abstract class Controller
{
    // Abre la sesión de un usuario y lo manda a su panel (RF:0022)
    protected function iniciarSesion(Request $request, Usuario $usuario)
    {
        // Nuevo identificador de sesión para evitar la fijación de sesión (session fixation)
        $request->session()->regenerate();

        // El usuario se validó con adoptauy_login, que no ve nombres
        if (config('database.conexiones_por_rol'))
        {
            $usuario = Usuario::on(ConexionPorRol::CONEXIONES[$usuario->Tipo_Usuario])->find($usuario->ID_Usuario);
        }

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

    // Envía el correo para confirmar la dirección (RF:0043)
    protected function enviarConfirmacion(Usuario $usuario)
    {
        $enlace = URL::signedRoute('correo.confirmar', ['id' => $usuario->ID_Usuario]);

        // Si el correo falla, la cuenta igual queda creada y se puede pedir otro (RNF:0010)
        try
        {
            Mail::to($usuario->Email)->send(new ConfirmarCorreo($usuario, $enlace));
        }
        catch (\Exception $e)
        {
            report($e);
        }
    }
}
