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
        $limites = ['registro' => 5, 'ingresar' => 10, 'correo' => 3, 'formularios' => 10];

        foreach ($limites as $nombre => $porMinuto)
        {
            RateLimiter::for($nombre, function (Request $request) use ($porMinuto)
            {
                // En la prueba de carga (LIMITES_ACTIVOS=false) no hay límite
                if (!config('app.limites'))
                {
                    return Limit::none();
                }

                return Limit::perMinute($porMinuto)->by($request->ip() . '|' . $request->path());
            });
        }
    }
}
