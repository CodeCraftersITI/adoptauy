<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use App\Models\Usuario;
use App\Rules\CedulaUruguaya;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

// Continuar con Google con Laravel Socialite (RF:0066)
class GoogleController extends Controller
{
    // Mismo mensaje para todo rechazo: no revela si el correo existe ni por qué falló
    public const MENSAJE_ERROR = 'No se pudo iniciar sesión con esa cuenta de Google.';

    // GET /auth/google
    public function redirigir()
    {
        return Socialite::driver('google')->redirect();
    }

    // GET /auth/google/callback: Google vuelve acá con el code (pasos 3 a 6)
    public function callback(Request $request)
    {
        // Socialite comprueba el state, canjea el code por el token y pide el perfil
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

        // Se busca primero por el identificador de Google y después por el correo
        $usuario = Usuario::paraLogin()->where('Google_ID', $google->getId())->first();
        if (!$usuario)
        {
            $usuario = Usuario::paraLogin()->where('Email', $email)->first();
        }

        // ---------- Ya existe una cuenta: login ----------
        if ($usuario)
        {
            // El Refugio y el Administrador no se vinculan con Google, y una cuenta inhabilitada o dada de baja no entra
            if ($usuario->Tipo_Usuario != 'Particular' || in_array($usuario->Estado_Usuario, ['Suspendido', 'Inhabilitado', 'Baja']))
            {
                return redirect('/ingresar')->with('error', self::MENSAJE_ERROR);
            }

            // Vincula la cuenta (RF:0066)
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
        // Faltan la CI y el teléfono, que Google no tiene
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

        // Nombre, apellido y teléfono usan las reglas compartidas del modelo Particular
        $request->validate([
            'ci' => ['required', new CedulaUruguaya, 'unique:Particular,CI'],
        ] + Particular::REGLAS, [
            'ci.required' => 'Ingresá tu cédula.',
            'ci.unique' => 'Ya existe una cuenta con esa cédula.',
        ] + Particular::MENSAJES);

        // Por si mientras completaba alguien registró ese correo o esa cuenta de Google
        if (Usuario::where('Email', $google['email'])->orWhere('Google_ID', $google['id'])->exists())
        {
            session()->forget('google_pendiente');

            return redirect('/ingresar')->with('error', self::MENSAJE_ERROR);
        }

        // Usuario y Particular se guardan juntos o ninguno (transacción, RNE11)
        $usuario = DB::transaction(function () use ($request, $google)
        {
            // Sin contraseña (NULL) y con el correo confirmado (RF:0066)
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
