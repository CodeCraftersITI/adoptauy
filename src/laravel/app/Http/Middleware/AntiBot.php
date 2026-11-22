<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Freno contra bots en los formularios públicos
class AntiBot
{
    public const SEGUNDOS_MINIMOS = 3;

    public const MENSAJE = 'No pudimos enviar el formulario. Revisá los datos y volvé a intentarlo en unos segundos.';

    public function handle(Request $request, Closure $next): Response
    {
        // Se puede apagar con ANTIBOT_ACTIVO=false (las pruebas que no son de este tema lo apagan)
        if (!config('app.antibot'))
        {
            return $next($request);
        }

        // Campo trampa lleno: lo completó un programa, no una persona
        if ($request->filled('sitio_web'))
        {
            return $this->rechazar($request);
        }

        // Hora en que se abrió el formulario; si falta o fue modificada, decrypt falla
        try
        {
            $abierto = decrypt($request->input('formulario_inicio'));
        }
        catch (\Exception $e)
        {
            return $this->rechazar($request);
        }

        if (time() - $abierto < self::SEGUNDOS_MINIMOS)
        {
            return $this->rechazar($request);
        }

        return $next($request);
    }

    // Vuelve al formulario con lo que había escrito (menos las contraseñas) y un mensaje
    private function rechazar(Request $request)
    {
        return back()->withInput($request->except('password', 'password_confirmation', 'password_actual'))
            ->with('error', self::MENSAJE);
    }
}
