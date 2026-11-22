<?php

namespace App\Http\Controllers;

// Página "Sobre nosotros"
class NosotrosController extends Controller
{
    // GET /nosotros
    public function index()
    {
        return view('nosotros');
    }
}
