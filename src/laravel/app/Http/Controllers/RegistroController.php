<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use App\Models\Usuario;
use App\Rules\CedulaUruguaya;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Registro de Particular (RF:0039).
 * La confirmación del correo (RF:0043) se agrega en el Sprint 5 (D67).
 */
class RegistroController extends Controller
{
    // GET /registro: muestra el formulario
    public function create()
    {
        return view('auth.registro');
    }

    // POST /registro: valida los datos y crea la cuenta
    public function store(Request $request)
    {
        // La cédula y el teléfono se guardan solo con números, y el correo en minúsculas.
        // El ?? '' evita errores cuando un campo llega vacío.
        $request->merge([
            'ci' => str_replace(['.', '-', ' '], '', $request->input('ci') ?? ''),
            'telefono' => str_replace(' ', '', $request->input('telefono') ?? ''),
            'email' => strtolower(trim($request->input('email') ?? '')),
        ]);

        // Criterio de aceptación de RF:0039. Si algo falla, Laravel vuelve al
        // formulario con los errores y no se ejecuta nada de lo que sigue.
        $request->validate([
            'ci' => ['required', new CedulaUruguaya, 'unique:Particular,CI'],
            'nombre' => 'required|min:2|max:50',
            'apellido' => 'required|min:2|max:50',
            'telefono' => 'required|regex:/^[0-9]{8,9}$/',
            'email' => 'required|email|max:255|unique:Usuario,Email',
            'password' => 'required|min:8',
        ], [
            'ci.required' => 'Ingresá tu cédula.',
            'ci.unique' => 'Ya existe una cuenta con esa cédula.',
            'nombre.required' => 'Ingresá tu nombre.',
            'nombre.min' => 'El nombre debe tener al menos 2 letras.',
            'nombre.max' => 'El nombre puede tener hasta 50 letras.',
            'apellido.required' => 'Ingresá tu apellido.',
            'apellido.min' => 'El apellido debe tener al menos 2 letras.',
            'apellido.max' => 'El apellido puede tener hasta 50 letras.',
            'telefono.required' => 'Ingresá tu teléfono.',
            'telefono.regex' => 'El teléfono debe tener 8 o 9 números.',
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'email.max' => 'El correo puede tener hasta 255 caracteres.',
            'email.unique' => 'Ya existe una cuenta con ese correo electrónico.',
            'password.required' => 'Ingresá una contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        // El Usuario y su fila de Particular se guardan juntos: si falla uno,
        // no se guarda ninguno (transacción, RNE11)
        DB::transaction(function () use ($request)
        {
            // Tipo_Usuario no está en $fillable, por eso se asigna a mano
            $usuario = new Usuario();
            $usuario->Email = $request->input('email');
            $usuario->Contraseña = Hash::make($request->input('password'));   // RNF:0007: hash bcrypt
            $usuario->Tipo_Usuario = 'Particular';
            $usuario->save();

            Particular::create([
                'ID_Usuario' => $usuario->ID_Usuario,
                'CI' => $request->input('ci'),
                'Nombre' => $request->input('nombre'),
                'Apellido' => $request->input('apellido'),
                'Telefono' => $request->input('telefono'),
            ]);
        });

        return redirect('/ingresar')->with('exito', 'Tu cuenta fue creada. Ya podés ingresar con tu correo y contraseña.');
    }
}
