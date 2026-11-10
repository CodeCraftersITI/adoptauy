<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas programadas: corren todos los días a las 03:00, hora de Montevideo (D71).
// El contenedor scheduler las ejecuta con "php artisan schedule:work".

// Archiva las mascotas perdidas y encontradas vencidas (RF:0047)
Schedule::command('publicaciones:archivar-vencidas')->dailyAt('03:00');
