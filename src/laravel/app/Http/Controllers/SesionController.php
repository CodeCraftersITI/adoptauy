<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Inicio de sesión (RF:0022) y cierre de sesión (RF:0023)
class SesionController extends Controller
{
    // Mismo mensaje si el correo no existe o si la contraseña está mal
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

        // Buscamos al usuario por su correo
        $usuario = Usuario::paraLogin()->where('Email', strtolower($request->input('email')))->first();

        if (!$usuario || !Hash::check($request->input('password'), $usuario->Contraseña))
        {
            return back()->with('error', self::MENSAJE_ERROR)->withInput($request->only('email'));
        }

        // Una cuenta suspendida, inhabilitada o dada de baja no entra, con el mismo mensaje
        if (in_array($usuario->Estado_Usuario, ['Suspendido', 'Inhabilitado', 'Baja']))
        {
            return back()->with('error', self::MENSAJE_ERROR)->withInput($request->only('email'));
        }

        // Un Particular entra solo con el correo confirmado (RF:0043), con el mismo mensaje
        if ($usuario->Tipo_Usuario == 'Particular' && !$usuario->Email_Verificado)
        {
            return back()->with('error', self::MENSAJE_ERROR)->withInput($request->only('email'));
        }

        // Un refugio solo entra si el Administrador ya lo validó
        if ($usuario->Tipo_Usuario == 'Refugio' && $usuario->refugio->Estado_Validacion != 'Validado')
        {
            return back()->with('error', self::MENSAJE_ERROR)->withInput($request->only('email'));
        }

        // Abre la sesión y lo manda a su panel (método del Controller base)
        return $this->iniciarSesion($request, $usuario);
    }

    // POST /salir: cierra la sesión
    public function destroy(Request $request)
    {
        $request->session()->invalidate();       // borra todos los datos de la sesión
        $request->session()->regenerateToken();  // nuevo token CSRF

        return redirect('/')->with('exito', 'Cerraste sesión.');
    }
}
