<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\SesionController;
use Illuminate\Support\Facades\Route;

// ---------- Rutas públicas ----------

// Página de inicio (RF:0040, RF:0048)
Route::get('/', [InicioController::class, 'index']);

// Registro de Particular (RF:0039)
Route::get('/registro', [RegistroController::class, 'create']);
Route::post('/registro', [RegistroController::class, 'store']);

// Inicio y cierre de sesión (RF:0022, RF:0023)
Route::get('/ingresar', [SesionController::class, 'create']);
Route::post('/ingresar', [SesionController::class, 'store']);
Route::post('/salir', [SesionController::class, 'destroy']);

// ---------- Rutas privadas, protegidas por rol con el middleware VerificarRol ----------

// Particular y Refugio
Route::middleware('rol:Particular,Refugio')->group(function ()
{
    Route::get('/cuenta', [CuentaController::class, 'index']);
});

// Administrador
Route::middleware('rol:Administrador')->group(function ()
{
    Route::get('/admin', [AdminController::class, 'index']);
});
