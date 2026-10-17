<?php

namespace App\Http\Controllers;

/**
 * Panel de administración (RF:0022: a donde va el Administrador después de ingresar).
 * Las secciones de refugios, reportes y publicaciones se agregan después.
 */
class AdminController extends Controller
{
    // GET /admin (protegida con el middleware rol:Administrador)
    public function index()
    {
        return view('admin.index');
    }
}
