<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudRefugioRecibida;
use App\Models\Refugio;
use App\Models\RefugioDatoContacto;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Solicitud de registro de un refugio (RF:0034).
 * La solicitud queda "Pendiente de validación" hasta que el Administrador la
 * valida o la rechaza (AdminController). Mientras tanto no puede iniciar sesión.
 */
class RegistroRefugioController extends Controller
{
    // GET /registro-refugio: muestra el formulario
    public function create()
    {
        return view('auth.registro-refugio');
    }

    // POST /registro-refugio: valida los datos, guarda los documentos y crea la solicitud
    public function store(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim($request->input('email') ?? '')),
        ]);

        // Documentos: PDF, JPG o PNG de hasta 8 MB (max se mide en kilobytes: 8 x 1024 = 8192)
        // Nombre, ubicación y contraseña usan las reglas compartidas de los modelos
        $request->validate([
            'contacto' => 'required|max:255',
            'certificado' => 'required|file|mimes:pdf,jpg,jpeg,png|max:8192',
            'comprobante' => 'required|file|mimes:pdf,jpg,jpeg,png|max:8192',
            'email' => 'required|email|max:255|unique:' . Usuario::CONEXION_REGISTRO . '.Usuario,Email',
            'password' => Usuario::REGLA_CONTRASENA,
        ] + Refugio::REGLAS, [
            'contacto.required' => 'Ingresá un teléfono o una red social.',
            'contacto.max' => 'El contacto puede tener hasta 255 caracteres.',
            'certificado.required' => 'Adjuntá el certificado de personería.',
            'certificado.mimes' => 'El certificado tiene que ser PDF, JPG o PNG.',
            'certificado.max' => 'El certificado puede pesar hasta 8 MB.',
            'comprobante.required' => 'Adjuntá el comprobante de domicilio.',
            'comprobante.mimes' => 'El comprobante tiene que ser PDF, JPG o PNG.',
            'comprobante.max' => 'El comprobante puede pesar hasta 8 MB.',
            'email.required' => 'Ingresá el correo electrónico.',
            'email.email' => 'Ingresá un correo electrónico válido.',
            'email.unique' => 'Ya existe una cuenta con ese correo electrónico.',
        ] + Refugio::MENSAJES + Usuario::MENSAJES_CONTRASENA);

        // Los documentos se guardan en storage/app/private: no se pueden abrir desde
        // el navegador, solo el Administrador los descarga desde su panel
        $rutaCertificado = $request->file('certificado')->store('documentos-refugios');
        $rutaComprobante = $request->file('comprobante')->store('documentos-refugios');

        // Usuario, Refugio y su contacto se guardan juntos (transacción, RNE11), con el usuario
        // de MySQL adoptauy_publico, que solo puede insertar cuentas nuevas (16.3.4, RNF:0007)
        $refugio = DB::connection(Usuario::CONEXION_REGISTRO)->transaction(function () use ($request, $rutaCertificado, $rutaComprobante)
        {
            $usuario = new Usuario();
            $usuario->setConnection(Usuario::CONEXION_REGISTRO);
            $usuario->Email = $request->input('email');
            $usuario->Contraseña = Hash::make($request->input('password'));
            $usuario->Tipo_Usuario = 'Refugio';
            $usuario->save();

            // Estado_Validacion queda en 'Pendiente de validación' (valor por defecto de la tabla)
            $refugio = Refugio::on(Usuario::CONEXION_REGISTRO)->create([
                'ID_Usuario' => $usuario->ID_Usuario,
                'Nombre_Refugio' => $request->input('nombre_refugio'),
                'Ubicacion' => $request->input('ubicacion'),
                'Certificado_Personeria' => $rutaCertificado,
                'Comprobante_Domicilio' => $rutaComprobante,
            ]);

            RefugioDatoContacto::on(Usuario::CONEXION_REGISTRO)->create([
                'ID_Usuario' => $usuario->ID_Usuario,
                'Dato_Contacto' => $request->input('contacto'),
            ]);

            return $refugio;
        });

        // El correo se arma con la solicitud leída por la conexión de la aplicación:
        // adoptauy_publico no puede leer la fila completa del refugio
        $refugio = Refugio::findOrFail($refugio->ID_Usuario);

        // Correo de recepción (RF:0063). Si el servicio de correo falla, la solicitud
        // igual queda guardada y el error se registra en el log (RNF:0010)
        try
        {
            Mail::to($request->input('email'))->send(new SolicitudRefugioRecibida($refugio));
        }
        catch (\Exception $e)
        {
            report($e);
        }

        return redirect('/')->with('exito', 'Recibimos la solicitud de ' . $refugio->Nombre_Refugio
            . '. El Administrador va a revisar la documentación en un máximo de 5 días hábiles y te avisamos por correo.');
    }
}
