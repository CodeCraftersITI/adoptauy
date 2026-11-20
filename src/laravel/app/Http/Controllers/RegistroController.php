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
 * Al crear la cuenta se envía el correo para confirmar la dirección (RF:0043); sin
 * confirmarla no se puede iniciar sesión (D67).
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
        // Nombre, apellido, teléfono y contraseña usan las reglas compartidas de los modelos
        $request->validate([
            // La cédula y el correo repetidos se buscan con adoptauy_publico (16.3.4)
            'ci' => ['required', new CedulaUruguaya, 'unique:' . Usuario::CONEXION_REGISTRO . '.Particular,CI'],
            'email' => 'required|email|max:255|unique:' . Usuario::CONEXION_REGISTRO . '.Usuario,Email',
            'password' => Usuario::REGLA_CONTRASENA,
        ] + Particular::REGLAS, [
            'ci.required' => 'Ingresá tu cédula.',
            'ci.unique' => 'Ya existe una cuenta con esa cédula.',
            'email.required' => 'Ingresá tu correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'email.max' => 'El correo puede tener hasta 255 caracteres.',
            'email.unique' => 'Ya existe una cuenta con ese correo electrónico.',
        ] + Particular::MENSAJES + Usuario::MENSAJES_CONTRASENA);

        // El Usuario y su fila de Particular se guardan juntos: si falla uno,
        // no se guarda ninguno (transacción, RNE11). Se guardan con el usuario de MySQL
        // adoptauy_publico, que solo puede insertar cuentas nuevas (16.3.4, RNF:0007)
        $usuario = new Usuario();
        $usuario->setConnection(Usuario::CONEXION_REGISTRO);

        DB::connection(Usuario::CONEXION_REGISTRO)->transaction(function () use ($request, $usuario)
        {
            // Tipo_Usuario no está en $fillable, por eso se asigna a mano.
            // Email_Verificado queda vacío (NULL) hasta que confirme el correo (RF:0043)
            $usuario->Email = $request->input('email');
            $usuario->Contraseña = Hash::make($request->input('password'));   // RNF:0007: hash bcrypt
            $usuario->Tipo_Usuario = 'Particular';
            $usuario->save();

            Particular::on(Usuario::CONEXION_REGISTRO)->create([
                'ID_Usuario' => $usuario->ID_Usuario,
                'CI' => $request->input('ci'),
                'Nombre' => $request->input('nombre'),
                'Apellido' => $request->input('apellido'),
                'Telefono' => $request->input('telefono'),
            ]);
        });

        // Correo con el enlace para confirmar la dirección (RF:0043, RF:0063). El correo usa el
        // nombre de la persona, que adoptauy_publico no puede leer: se lee con la conexión de la aplicación
        $usuario = Usuario::findOrFail($usuario->ID_Usuario);
        $this->enviarConfirmacion($usuario);

        return redirect('/ingresar')->with('exito', 'Tu cuenta fue creada. Te enviamos un correo a ' . $usuario->Email
            . ': abrí el enlace para confirmarlo y después ingresá.');
    }
}
