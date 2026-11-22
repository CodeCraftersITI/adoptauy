<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use App\Models\Refugio;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

// "Mi perfil" del Particular y del Refugio (RF:0016, RF:0029, RF:0067)
class PerfilController extends Controller
{
    // GET /perfil
    public function index()
    {
        $usuario = Usuario::find(session('id_usuario'));

        return view('perfil.index', ['usuario' => $usuario]);
    }

    // PUT /perfil/datos
    public function guardarDatos(Request $request)
    {
        $usuario = Usuario::find(session('id_usuario'));

        if ($usuario->Tipo_Usuario == 'Refugio')
        {
            $this->guardarDatosRefugio($request, $usuario->refugio);
        }
        else
        {
            $this->guardarDatosParticular($request, $usuario->particular);
        }

        // El nombre que se muestra en "Mi cuenta" sale de la sesión
        session(['nombre' => Usuario::find($usuario->ID_Usuario)->nombreVisible()]);

        return redirect('/perfil')->with('exito', 'Guardaste los cambios de tu perfil.');
    }

    // Datos del Particular: nombre, apellido y teléfono (RF:0016)
    private function guardarDatosParticular(Request $request, $particular)
    {
        $request->merge(['telefono' => str_replace(' ', '', $request->input('telefono') ?? '')]);
        $request->validate(Particular::REGLAS, Particular::MENSAJES);

        $particular->Nombre = $request->input('nombre');
        $particular->Apellido = $request->input('apellido');
        $particular->Telefono = $request->input('telefono');
        $particular->save();
    }

    // Refugio (RF:0029): nombre, ubicación, contactos (uno por línea) y foto
    private function guardarDatosRefugio(Request $request, $refugio)
    {
        // Los contactos llegan en un cuadro de texto, uno por línea (reglas compartidas del modelo)
        $contactos = Refugio::contactosDesdeTexto($request->input('contactos'));
        $request->merge(['lista_contactos' => $contactos]);

        $request->validate(Refugio::REGLAS + Refugio::REGLAS_CONTACTOS + [
            'foto' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:8192',
        ], Refugio::MENSAJES + Refugio::MENSAJES_CONTACTOS + [
            'foto.mimes' => 'La foto tiene que ser JPG, PNG o WEBP.',
            'foto.max' => 'La foto puede pesar hasta 8 MB.',
        ]);

        // Foto nueva: reemplaza a la anterior
        if ($request->hasFile('foto'))
        {
            if ($refugio->URL_Foto)
            {
                Storage::disk('public')->delete($refugio->URL_Foto);
            }
            $refugio->URL_Foto = $request->file('foto')->store('refugios', 'public');
        }

        // Nombre, ubicación y contactos se guardan juntos (método del modelo, también lo usa el Administrador)
        $refugio->actualizarDatos($request->input('nombre_refugio'), $request->input('ubicacion'), $contactos);
    }

    // PUT /perfil/contrasena (RF:0067)
    public function guardarContrasena(Request $request)
    {
        $usuario = Usuario::find(session('id_usuario'));
        $tieneContrasena = $usuario->Contraseña !== null;

        $reglas = ['password' => Usuario::REGLA_CONTRASENA . '|confirmed'];
        if ($tieneContrasena)
        {
            $reglas['password_actual'] = 'required';
        }

        $request->validate($reglas, Usuario::MENSAJES_CONTRASENA + [
            'password.confirmed' => 'Las dos contraseñas nuevas no coinciden.',
            'password_actual.required' => 'Ingresá tu contraseña actual.',
        ]);

        // Para cambiarla se exige la actual; para definirla (cuenta de Google sin contraseña), no
        if ($tieneContrasena && !Hash::check($request->input('password_actual'), $usuario->Contraseña))
        {
            return back()->withErrors(['password_actual' => 'La contraseña actual no es correcta.']);
        }

        // Se guarda solo el hash bcrypt (RNF:0007)
        $usuario->Contraseña = Hash::make($request->input('password'));
        $usuario->save();

        return redirect('/perfil')->with('exito', $tieneContrasena
            ? 'Cambiaste tu contraseña.'
            : 'Definiste tu contraseña. Desde ahora también podés ingresar con tu correo y contraseña.');
    }
}
