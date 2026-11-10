<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\DetallePublicacionController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\NosotrosController;
use App\Http\Controllers\PublicacionController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\RegistroRefugioController;
use App\Http\Controllers\SesionController;
use App\Http\Controllers\SolicitudController;
use Illuminate\Support\Facades\Route;

// ---------- Rutas públicas ----------

// Página de inicio (RF:0040, RF:0048)
Route::get('/', [InicioController::class, 'index']);

// Detalle de una publicación: pública, sin necesidad de sesión
Route::get('/publicaciones/{id}', [DetallePublicacionController::class, 'show']);

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

// Continuar con Google (RF:0066): ir a Google, volver de Google y completar CI y teléfono
Route::get('/auth/google', [GoogleController::class, 'redirigir']);
Route::get('/auth/google/callback', [GoogleController::class, 'callback']);
Route::get('/auth/google/completar', [GoogleController::class, 'completar']);
Route::post('/auth/google/completar', [GoogleController::class, 'guardar']);

// ---------- Rutas privadas, protegidas por rol con el middleware VerificarRol ----------

// Particular y Refugio
Route::middleware('rol:Particular,Refugio')->group(function ()
{
    Route::get('/cuenta', [CuentaController::class, 'index']);

    // Publicar mascota en adopción (RF:0017, RF:0024), perdida (RF:0046) y encontrada (RF:0064)
    Route::get('/publicar/adopcion', [PublicacionController::class, 'crearAdopcion']);
    Route::post('/publicar/adopcion', [PublicacionController::class, 'guardarAdopcion']);
    Route::get('/publicar/perdida', [PublicacionController::class, 'crearPerdida']);
    Route::post('/publicar/perdida', [PublicacionController::class, 'guardarPerdida']);
    Route::get('/publicar/encontrada', [PublicacionController::class, 'crearEncontrada']);
    Route::post('/publicar/encontrada', [PublicacionController::class, 'guardarEncontrada']);

    // Solicitudes recibidas en todas mis publicaciones (RF:0050)
    Route::get('/solicitudes/recibidas', [SolicitudController::class, 'recibidas']);
});

// Solo Particular: el Refugio no solicita adopciones (RF:0049, D49)
Route::middleware('rol:Particular')->group(function ()
{
    Route::get('/publicaciones/{id}/solicitar', [SolicitudController::class, 'crear']);
    Route::post('/publicaciones/{id}/solicitar', [SolicitudController::class, 'guardar']);

    // Mis solicitudes enviadas (RF:0054)
    Route::get('/solicitudes', [SolicitudController::class, 'enviadas']);
});

// Solicitudes de una publicación: su publicante o el Administrador (RF:0050, RNF:0013)
Route::middleware('rol:Particular,Refugio,Administrador')->group(function ()
{
    Route::get('/publicaciones/{id}/solicitudes', [SolicitudController::class, 'dePublicacion']);
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
