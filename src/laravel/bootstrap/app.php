<?php

use App\Http\Middleware\AntiBot;
use App\Http\Middleware\ConexionPorRol;
use App\Http\Middleware\VerificarRol;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Nombre corto para usar en las rutas: ->middleware('rol:Administrador')
        // Usuario de MySQL según el rol de la sesión (privilegio mínimo, 16.3.4)
        $middleware->web(append: [ConexionPorRol::class]);

        $middleware->alias([
            'rol' => VerificarRol::class,
            'antibot' => AntiBot::class,   // ->middleware('antibot') en los formularios públicos
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
