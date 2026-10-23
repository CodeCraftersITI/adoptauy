<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Inicio de sesión (RF:0022) y cierre de sesión (RF:0023), como en el teórico:
 * Hash::check() para la contraseña y los datos del usuario guardados en la sesión.
 *
 * Hito 2: se controla el correo, la contraseña y que el refugio esté Validado
 * (adelantado porque el registro de refugios entra en este hito). Desde el Sprint 5
 * también se rechaza el correo sin confirmar y la cuenta inhabilitada o dada de baja,
 * con el mismo mensaje (D67).
 */
class SesionController extends Controller
{
    // Mismo mensaje si el correo no existe o si la contraseña está mal:
    // así no se revela qué correos están registrados (regla de seguridad del equipo)
    public const MENSAJE_ERROR = 'El correo o la contraseña no son correctos.';

    // GET /ingresar: muestra el formulario
    public function create()
    {
        return view('auth.ingresar');
    }

    // POST /ingresar: inicia la sesión
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'password.required' => 'Ingresá tu contraseña.',
        ]);

        // Buscamos al usuario por su correo (where + first, como en el teórico)
        $usuario = Usuario::where('Email', strtolower($request->input('email')))->first();

        if (!$usuario || !Hash::check($request->input('password'), $usuario->Contraseña))
        {
            return back()->with('error', self::MENSAJE_ERROR)->withInput($request->only('email'));
        }

        // Un refugio solo entra si el Administrador ya lo validó (13.2.1). Se usa el mismo
        // mensaje para no revelar que el correo existe ni en qué estado está la solicitud.
        if ($usuario->Tipo_Usuario == 'Refugio' && $usuario->refugio->Estado_Validacion != 'Validado')
        {
            return back()->with('error', self::MENSAJE_ERROR)->withInput($request->only('email'));
        }

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

    // POST /salir: cierra la sesión
    public function destroy(Request $request)
    {
        $request->session()->invalidate();       // borra todos los datos de la sesión
        $request->session()->regenerateToken();  // nuevo token CSRF

        return redirect('/')->with('exito', 'Cerraste sesión.');
    }
}
