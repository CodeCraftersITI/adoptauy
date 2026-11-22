<?php

namespace App\Http\Controllers;

use App\Mail\ConfirmarCorreo;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

abstract class Controller
{
    // Abre la sesión de un usuario y lo manda a su panel (RF:0022). La usan el login con
    // correo y contraseña (SesionController) y el de Google (GoogleController).
    protected function iniciarSesion(Request $request, Usuario $usuario)
    {
        // La cuenta llega leída con adoptauy_login o creada con adoptauy_publico, que no pueden
        // leer el nombre de la persona ni del refugio. La sesión se abre con la cuenta leída
        // por la conexión de la aplicación (16.3.4)
        $usuario = Usuario::findOrFail($usuario->ID_Usuario);

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

    // Envía el correo para confirmar la dirección (RF:0043). La usan el registro
    // (RegistroController) y el reenvío (ConfirmacionCorreoController).
    // URL::signedRoute arma el enlace con una firma: si alguien le cambia el número de usuario,
    // la firma deja de coincidir y el enlace se rechaza (CP-03).
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
