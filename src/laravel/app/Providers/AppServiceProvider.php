<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Límites de envíos por minuto contra bots (pedido del docente). Se usan en las rutas con
        // ->middleware('throttle:registro'). Cada límite se cuenta por IP y por formulario
        // (by), así reportar varias veces no le gasta el cupo de iniciar sesión a la misma persona.
        // Si se pasa del límite, Laravel muestra la página errors/429.
        RateLimiter::for('registro', function (Request $request)
        {
            return Limit::perMinute(5)->by($request->ip() . '|' . $request->path());
        });

        RateLimiter::for('ingresar', function (Request $request)
        {
            return Limit::perMinute(10)->by($request->ip() . '|' . $request->path());
        });

        RateLimiter::for('correo', function (Request $request)
        {
            return Limit::perMinute(3)->by($request->ip() . '|' . $request->path());
        });

        RateLimiter::for('formularios', function (Request $request)
        {
            return Limit::perMinute(10)->by($request->ip() . '|' . $request->path());
        });
    }
}
