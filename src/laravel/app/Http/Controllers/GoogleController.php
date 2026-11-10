<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use App\Models\Usuario;
use App\Rules\CedulaUruguaya;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

/**
 * Continuar con Google (RF:0066, D93, RNE27), con Laravel Socialite como en el teórico.
 * Solo para el Particular: el Refugio y el Administrador ingresan con correo y contraseña.
 *
 * Registro y login son el mismo botón; la app decide según lo que encuentre en Usuario:
 *  - Google_ID o correo de un Particular activo: vincula la cuenta y abre la sesión.
 *  - No hay cuenta: pide la CI y el teléfono (pantalla Completar datos) y crea el Particular
 *    sin contraseña y con el correo confirmado.
 *  - Correo de un Refugio o del Administrador, o cuenta inhabilitada o dada de baja:
 *    mensaje genérico que no revela si el correo existe.
 */
class GoogleController extends Controller
{
    // Mismo mensaje para todo rechazo: no revela si el correo existe ni por qué falló
    public const MENSAJE_ERROR = 'No se pudo iniciar sesión con esa cuenta de Google.';

    // GET /auth/google: manda al usuario a la pantalla de Google (pasos 1 y 2 de OAuth).
    // Socialite arma la URL con el client_id, la redirect_uri, los scopes y el state.
    public function redirigir()
    {
        return Socialite::driver('google')->redirect();
    }

    // GET /auth/google/callback: Google vuelve acá con el code (pasos 3 a 6)
    public function callback(Request $request)
    {
        // Socialite comprueba el state, canjea el code por el token y pide el perfil.
        // Falla si la persona tocó "Cancelar" o si la respuesta no es válida:
        // en ese caso no se crea ni se modifica ninguna cuenta.
        try
        {
            $google = Socialite::driver('google')->user();
        }
        catch (Exception $e)
        {
            return redirect('/ingresar')->with('error', self::MENSAJE_ERROR);
        }

        // Solo se aceptan cuentas de Google con el correo verificado (RF:0066)
        $verificado = filter_var($google->user['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if (!$verificado || !$google->getEmail())
        {
            return redirect('/ingresar')->with('error', self::MENSAJE_ERROR);
        }

        $email = strtolower($google->getEmail());

        // Se busca primero por el identificador de Google (no cambia nunca) y después por el correo
        $usuario = Usuario::where('Google_ID', $google->getId())->first();
        if (!$usuario)
        {
            $usuario = Usuario::where('Email', $email)->first();
        }

        // ---------- Ya existe una cuenta: login ----------
        if ($usuario)
        {
            // El Refugio y el Administrador nunca se vinculan con Google (D93), y una cuenta
            // inhabilitada o dada de baja no entra. Mismo mensaje en todos los casos.
            if ($usuario->Tipo_Usuario != 'Particular' || in_array($usuario->Estado_Usuario, ['Inhabilitado', 'Baja']))
            {
                return redirect('/ingresar')->with('error', self::MENSAJE_ERROR);
            }

            // Vincula la cuenta: guarda el Google_ID si no lo tenía y da el correo por
            // confirmado, porque Google ya lo verificó (decisión del equipo, RF:0066)
            if (!$usuario->Google_ID)
            {
                $usuario->Google_ID = $google->getId();
            }
            if (!$usuario->Email_Verificado)
            {
                $usuario->Email_Verificado = now();
            }
            $usuario->save();

            return $this->iniciarSesion($request, $usuario);
        }

        // ---------- No hay cuenta: registro ----------
        // Faltan la CI y el teléfono, que Google no tiene. Guardamos lo que mandó Google en
        // la sesión (no en el formulario, así nadie puede cambiar el correo ni el Google_ID)
        // y pasamos a la pantalla Completar datos.
        session(['google_pendiente' => [
            'id' => $google->getId(),
            'email' => $email,
            'nombre' => $google->user['given_name'] ?? $google->getName(),
            'apellido' => $google->user['family_name'] ?? '',
        ]]);

        return redirect('/auth/google/completar');
    }

    // GET /auth/google/completar: pide la CI y el teléfono; nombre y apellido precargados
    public function completar()
    {
        if (!session('google_pendiente'))
        {
            return redirect('/ingresar')->with('error', 'Primero elegí tu cuenta de Google.');
        }

        return view('auth.completar-google', ['google' => session('google_pendiente')]);
    }

    // POST /auth/google/completar: crea el Particular con los datos de Google y los cargados
    public function guardar(Request $request)
    {
        $google = session('google_pendiente');

        if (!$google)
        {
            return redirect('/ingresar')->with('error', 'Primero elegí tu cuenta de Google.');
        }

        // Mismos datos y validaciones que el registro con formulario (RF:0039, 13.2.1)
        $request->merge([
            'ci' => str_replace(['.', '-', ' '], '', $request->input('ci') ?? ''),
            'telefono' => str_replace(' ', '', $request->input('telefono') ?? ''),
        ]);

        $request->validate([
            'ci' => ['required', new CedulaUruguaya, 'unique:Particular,CI'],
            'nombre' => 'required|min:2|max:50',
            'apellido' => 'required|min:2|max:50',
            'telefono' => 'required|regex:/^[0-9]{8,9}$/',
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
        ]);

        // Por si mientras completaba alguien registró ese correo o esa cuenta de Google
        if (Usuario::where('Email', $google['email'])->orWhere('Google_ID', $google['id'])->exists())
        {
            session()->forget('google_pendiente');

            return redirect('/ingresar')->with('error', self::MENSAJE_ERROR);
        }

        // Usuario y Particular se guardan juntos o ninguno (transacción, RNE11)
        $usuario = DB::transaction(function () use ($request, $google)
        {
            // Sin contraseña (NULL) y con el correo confirmado (RF:0066, RNE27).
            // Tipo_Usuario y Google_ID no están en $fillable: se asignan a mano.
            $usuario = new Usuario();
            $usuario->Email = $google['email'];
            $usuario->Tipo_Usuario = 'Particular';
            $usuario->Google_ID = $google['id'];
            $usuario->Email_Verificado = now();
            $usuario->save();

            Particular::create([
                'ID_Usuario' => $usuario->ID_Usuario,
                'CI' => $request->input('ci'),
                'Nombre' => $request->input('nombre'),
                'Apellido' => $request->input('apellido'),
                'Telefono' => $request->input('telefono'),
            ]);

            return $usuario;
        });

        session()->forget('google_pendiente');

        return $this->iniciarSesion($request, $usuario)
            ->with('exito', 'Tu cuenta fue creada con Google. ¡Bienvenido/a a AdoptaUY!');
    }
}
