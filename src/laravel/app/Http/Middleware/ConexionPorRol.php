<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

// Elige el usuario de MySQL de cada pedido según el rol de la sesión (docs/base-de-datos/usuarios.sql)
class ConexionPorRol
{
    public const CONEXIONES = [
        'Particular' => 'particular',
        'Refugio' => 'refugio',
        'Administrador' => 'admin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (config('database.conexiones_por_rol'))
        {
            DB::setDefaultConnection(self::CONEXIONES[session('tipo')] ?? 'publico');
        }

        return $next($request);
    }
}
