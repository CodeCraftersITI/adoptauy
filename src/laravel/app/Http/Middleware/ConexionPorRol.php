<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Elige el usuario de MySQL con el que se atiende cada pedido, según el rol de la sesión
 * (privilegio mínimo, 16.3.4 del integrador, docs/base-de-datos/usuarios.sql):
 *   sin sesión     -> adoptauy_publico
 *   Particular     -> adoptauy_particular
 *   Refugio        -> adoptauy_refugio
 *   Administrador  -> adoptauy_admin
 * Así, aunque hubiera un error en el código, la base no deja que un invitado lea cédulas ni que
 * un Particular borre usuarios o cambie el estado de un refugio.
 * El inicio de sesión usa adoptauy_login y los umbrales de reportes adoptauy_sistema, con usar().
 * Se registra en bootstrap/app.php, en el grupo "web" (después de iniciar la sesión).
 */
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
            DB::setDefaultConnection(self::conexionDe(session('tipo')));
        }

        return $next($request);
    }

    // Conexión de un rol; sin sesión, la del Usuario Invitado
    public static function conexionDe($tipo)
    {
        return self::CONEXIONES[$tipo] ?? 'publico';
    }

    // Hace una tarea con otro usuario de MySQL y después vuelve al de antes.
    // Ej.: ConexionPorRol::usar('sistema', function () { ... });
    public static function usar($conexion, Closure $tarea)
    {
        if (!config('database.conexiones_por_rol'))
        {
            return $tarea();
        }

        $anterior = DB::getDefaultConnection();
        DB::setDefaultConnection($conexion);

        try
        {
            return $tarea();
        }
        finally
        {
            DB::setDefaultConnection($anterior);
        }
    }
}
