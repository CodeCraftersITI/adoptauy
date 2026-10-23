<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\NosotrosController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\RegistroRefugioController;
use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

// ---------- Rutas públicas ----------

// Página de inicio (RF:0040, RF:0048)
Route::get('/', [InicioController::class, 'index']);

// Sobre nosotros
Route::get('/nosotros', [NosotrosController::class, 'index']);

// Registro de Particular (RF:0039)
Route::get('/registro', [RegistroController::class, 'create']);
Route::post('/registro', [RegistroController::class, 'store']);

// Solicitud de registro de refugio (RF:0034)
Route::get('/registro-refugio', [RegistroRefugioController::class, 'create']);
Route::post('/registro-refugio', [RegistroRefugioController::class, 'store']);

// Inicio y cierre de sesión (RF:0022, RF:0023)
Route::get('/ingresar', [SesionController::class, 'create']);
Route::post('/ingresar', [SesionController::class, 'store']);
Route::post('/salir', [SesionController::class, 'destroy']);

// ---------- Rutas privadas, protegidas por rol con el middleware VerificarRol ----------

// Particular y Refugio
Route::middleware('rol:Particular,Refugio')->group(function ()
{
    Route::get('/cuenta', [CuentaController::class, 'index']);

    // Publicar mascota en adopción (RF:0017, RF:0024) y perdida (RF:0046)
    Route::get('/publicar/adopcion', [PublicacionController::class, 'crearAdopcion']);
    Route::post('/publicar/adopcion', [PublicacionController::class, 'guardarAdopcion']);
    Route::get('/publicar/perdida', [PublicacionController::class, 'crearPerdida']);
    Route::post('/publicar/perdida', [PublicacionController::class, 'guardarPerdida']);
});

// Administrador
Route::middleware('rol:Administrador')->group(function ()
{
    Route::get('/admin', [AdminController::class, 'index']);

    // Solicitudes de refugio: ver documentos, validar (RF:0005) y rechazar (RF:0044)
    Route::get('/admin/refugios/{id}/documento/{tipo}', [AdminController::class, 'verDocumento']);
    Route::put('/admin/refugios/{id}/validar', [AdminController::class, 'validar']);
    Route::put('/admin/refugios/{id}/rechazar', [AdminController::class, 'rechazar']);
});
