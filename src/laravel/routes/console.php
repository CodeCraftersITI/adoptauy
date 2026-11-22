<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Tareas programadas

// Archiva las mascotas perdidas y encontradas vencidas (RF:0047)
Schedule::command('publicaciones:archivar-vencidas')->dailyAt('03:00');

// Aviso de seguimiento del día 15 al adoptante (RF:0055)
Schedule::command('seguimientos:avisar')->dailyAt('03:00');
