<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use App\Models\Refugio;
use App\Models\RefugioDatoContacto;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * "Mi perfil" del Particular y del Refugio (rutas con rol:Particular,Refugio). Caso CP-16 y CP-50.
 *  - Particular: modifica nombre, apellido y teléfono (RF:0016). La cédula y el correo no se cambian.
 *  - Refugio: modifica nombre, ubicación, datos de contacto y foto (RF:0029). La ubicación en el
 *    mapa y las cuentas bancarias están en "Mi refugio".
 *  - Contraseña (RF:0067, D98): quien no tiene la define sin pedir la actual (cuentas creadas con
 *    Google); quien tiene una la cambia ingresando la actual. Vale para el Particular y el Refugio.
 * Las reglas de los datos están en los modelos y son las mismas que en el registro.
 */
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

        // El nombre que se muestra en "Mi cuenta" sale de la sesión: se actualiza
        session(['nombre' => $usuario->fresh()->nombreVisible()]);

        return redirect('/perfil')->with('exito', 'Guardaste los cambios de tu perfil.');
    }

    // Particular (RF:0016): solo nombre, apellido y teléfono. Aunque la petición traiga CI o
    // correo, no se usan: se asigna campo por campo (CP-16)
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
        // Los contactos llegan en un cuadro de texto, uno por línea: se separan y se limpian
        $contactos = array_values(array_unique(array_filter(array_map('trim', explode("\n", $request->input('contactos') ?? '')))));
        $request->merge(['lista_contactos' => $contactos]);

        $request->validate(Refugio::REGLAS + [
            'lista_contactos' => 'required|array|min:1',
            'lista_contactos.*' => 'max:255',
            'foto' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:8192',
        ], Refugio::MENSAJES + [
            'lista_contactos.required' => 'Ingresá al menos un dato de contacto.',
            'lista_contactos.min' => 'Ingresá al menos un dato de contacto.',
            'lista_contactos.*.max' => 'Cada dato de contacto puede tener hasta 255 caracteres.',
            'foto.mimes' => 'La foto tiene que ser JPG, PNG o WEBP.',
            'foto.max' => 'La foto puede pesar hasta 8 MB.',
        ]);

        DB::transaction(function () use ($request, $refugio, $contactos)
        {
            $refugio->Nombre_Refugio = $request->input('nombre_refugio');
            $refugio->Ubicacion = $request->input('ubicacion');

            // Foto nueva: reemplaza a la anterior
            if ($request->hasFile('foto'))
            {
                if ($refugio->URL_Foto)
                {
                    Storage::disk('public')->delete($refugio->URL_Foto);
                }
                $refugio->URL_Foto = $request->file('foto')->store('refugios', 'public');
            }

            $refugio->save();

            // Los contactos se reemplazan por la lista nueva (atributo multivaluado del refugio)
            RefugioDatoContacto::where('ID_Usuario', $refugio->ID_Usuario)->delete();
            foreach ($contactos as $contacto)
            {
                RefugioDatoContacto::create(['ID_Usuario' => $refugio->ID_Usuario, 'Dato_Contacto' => $contacto]);
            }
        });
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
