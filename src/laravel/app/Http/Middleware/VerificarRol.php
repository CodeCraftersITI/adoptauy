<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo a los usuarios con sesión iniciada y del rol indicado (RF:0022).
 * Es la misma idea que EsAdmin del teórico, pero sirve para cualquier rol:
 *
 *   Route::middleware('rol:Administrador')->group(...)          solo administradores
 *   Route::middleware('rol:Particular,Refugio')->group(...)     particulares y refugios
 *
 * Lo que va después de los dos puntos llega en $roles como un array.
 * El rol lo guarda SesionController en session('tipo') al iniciar sesión.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Sin sesión iniciada: lo mandamos a ingresar
        if (!session('id_usuario'))
        {
            return redirect('/ingresar')->with('error', 'Para continuar necesitás iniciar sesión.');
        }

        // Con sesión, pero de otro rol: acceso denegado
        if (!in_array(session('tipo'), $roles))
        {
            abort(403, 'No tenés permiso para entrar a esta página.');
        }

        return $next($request);
    }
}
