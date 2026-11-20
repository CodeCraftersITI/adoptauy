<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Confirmación del correo del Particular (RF:0043, CP-03). Desde el Sprint 5 no se puede
 * iniciar sesión sin confirmar (D67). La cuenta creada con Google ya viene confirmada (RF:0066).
 *  - GET /confirmar-correo/{id}: el enlace del correo. Si la firma es válida, confirma.
 *  - GET y POST /confirmar-correo: pedir que se reenvíe el correo, por si no llegó.
 */
class ConfirmacionCorreoController extends Controller
{
    // Mismo mensaje haya o no una cuenta con ese correo: así no se revela qué correos existen
    public const MENSAJE_REENVIO = 'Si ese correo es de una cuenta sin confirmar, te enviamos un nuevo enlace. Revisá tu bandeja de entrada.';

    // GET /confirmar-correo/{id}?signature=...
    public function confirmar(Request $request, string $id)
    {
        // hasValidSignature() comprueba que el enlace es el que mandamos y que nadie lo cambió
        if (!$request->hasValidSignature())
        {
            return redirect('/ingresar')->with('error', 'El enlace de confirmación no es válido. Pedí uno nuevo.');
        }

        // adoptauy_login es el que puede marcar el correo como confirmado (16.3.4.1)
        $usuario = Usuario::paraLogin()->find($id);

        if (!$usuario)
        {
            return redirect('/ingresar')->with('error', 'El enlace de confirmación no es válido. Pedí uno nuevo.');
        }

        // Si ya estaba confirmado no se cambia la fecha. Email_Verificado no está en $fillable
        if (!$usuario->Email_Verificado)
        {
            $usuario->Email_Verificado = now();
            $usuario->save();
        }

        return redirect('/ingresar')->with('exito', 'Tu correo quedó confirmado. Ya podés ingresar.');
    }

    // GET /confirmar-correo: formulario para pedir otro enlace
    public function create()
    {
        return view('auth.reenviar-confirmacion');
    }

    // POST /confirmar-correo: reenvía el correo si la cuenta es de un Particular sin confirmar
    public function reenviar(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
        ]);

        $usuario = Usuario::paraLogin()->where('Email', strtolower(trim($request->input('email'))))->first();

        if ($usuario && $usuario->Tipo_Usuario == 'Particular' && !$usuario->Email_Verificado)
        {
            $this->enviarConfirmacion($usuario);
        }

        return redirect('/ingresar')->with('exito', self::MENSAJE_REENVIO);
    }
}
