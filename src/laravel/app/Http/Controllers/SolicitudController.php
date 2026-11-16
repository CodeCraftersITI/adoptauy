<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudAdopcionRecibida;
use App\Models\Adopcion;
use App\Models\Particular;
use App\Models\PublicacionAnimal;
use App\Models\Seguimiento;
use App\Models\SolicitudAdopcion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Solicitudes de adopción (13.2.3):
 *  - Solicitar adopción (RF:0049, CP-22): solo un Particular (middleware rol:Particular).
 *  - Mis solicitudes enviadas (RF:0054, CP-26).
 *  - Solicitudes recibidas por el publicante (RF:0050, CP-23).
 *  - Rechazar una solicitud (RF:0051, CP-24) y concretar la adopción eligiendo al adoptante
 *    (RF:0052, CP-25): solo el publicante. Transiciones de 13.2.3:
 *      Pendiente -> Rechazada   rechazo individual; la publicación no cambia
 *      Pendiente -> Adoptada    el publicante la elige; las demás Pendientes pasan a Rechazada
 *                               y la publicación a Adoptado. Es definitivo (RNE19, D25).
 */
class SolicitudController extends Controller
{
    // GET /publicaciones/{id}/solicitar: muestra qué datos se comparten y pide el consentimiento
    public function crear(string $id)
    {
        $publicacion = $this->buscarEnAdopcion($id);

        // Si no se puede solicitar, vuelve al detalle con el motivo
        $motivo = $this->motivoParaNoSolicitar($publicacion);
        if ($motivo)
        {
            return redirect('/publicaciones/' . $id)->with('error', $motivo);
        }

        return view('solicitudes.crear', [
            'publicacion' => $publicacion,
            'particular' => Particular::find(session('id_usuario')),
        ]);
    }

    // POST /publicaciones/{id}/solicitar: guarda la solicitud y avisa al publicante
    public function guardar(Request $request, string $id)
    {
        $publicacion = $this->buscarEnAdopcion($id);

        $motivo = $this->motivoParaNoSolicitar($publicacion);
        if ($motivo)
        {
            return redirect('/publicaciones/' . $id)->with('error', $motivo);
        }

        // Ley 18.331: sin el consentimiento no se comparten los datos ni se envía nada
        $request->validate([
            'acepto' => 'accepted',
        ], [
            'acepto.accepted' => 'Para enviar la solicitud tenés que aceptar compartir tu nombre, teléfono y correo.',
        ]);

        // ID_Usuario no está en $fillable: el solicitante es siempre quien inició sesión
        $solicitud = new SolicitudAdopcion(['ID_Publicacion' => $publicacion->ID_Publicacion]);
        $solicitud->ID_Usuario = session('id_usuario');
        $solicitud->save();

        // Correo al publicante con los datos del solicitante (RF:0063). Si el correo falla,
        // la solicitud igual queda guardada y el publicante la ve en su lista (RNF:0010)
        try
        {
            Mail::to($publicacion->usuario->Email)->send(new SolicitudAdopcionRecibida($solicitud));
        }
        catch (\Exception $e)
        {
            report($e);
        }

        return redirect('/solicitudes')->with('exito', '¡Solicitud enviada! Quien publicó recibió tus datos y se va a comunicar con vos.');
    }

    // GET /solicitudes: las solicitudes que envió el Particular, con su estado (RF:0054)
    public function enviadas()
    {
        $solicitudes = SolicitudAdopcion::where('ID_Usuario', session('id_usuario'))
            ->orderBy('Fecha_Solicitud', 'desc')
            ->get();

        return view('solicitudes.enviadas', ['solicitudes' => $solicitudes]);
    }

    // GET /solicitudes/recibidas: todas las solicitudes de las publicaciones del usuario (RF:0050)
    public function recibidas()
    {
        $solicitudes = SolicitudAdopcion::whereHas('adopcion.publicacion', function ($consulta)
            {
                $consulta->where('ID_Usuario', session('id_usuario'));
            })
            ->orderBy('Fecha_Solicitud', 'desc')
            ->get();

        return view('solicitudes.recibidas', ['solicitudes' => $solicitudes, 'publicacion' => null]);
    }

    // GET /publicaciones/{id}/solicitudes: las solicitudes de una publicación (RF:0050, CP-23).
    // Solo las ven su publicante y el Administrador: tienen teléfonos y correos (RNF:0013)
    public function dePublicacion(string $id)
    {
        $publicacion = PublicacionAnimal::find($id);

        if (!$publicacion || $publicacion->Tipo_Publicacion != 'Adopción')
        {
            abort(404);
        }

        if ($publicacion->ID_Usuario != session('id_usuario') && session('tipo') != 'Administrador')
        {
            abort(403, 'Solo quien publicó puede ver las solicitudes.');
        }

        $solicitudes = $publicacion->adopcion->solicitudes()->orderBy('Fecha_Solicitud', 'desc')->get();

        return view('solicitudes.recibidas', ['solicitudes' => $solicitudes, 'publicacion' => $publicacion]);
    }

    // PUT /solicitudes/{id}/rechazar: rechazo individual (RF:0051)
    public function rechazar(string $id)
    {
        $solicitud = $this->buscarSolicitudRecibida($id);

        if ($solicitud->Estado_Solicitud != 'Pendiente')
        {
            return back()->with('error', 'Solo se puede rechazar una solicitud pendiente.');
        }

        // Estado_Solicitud no está en $fillable: se asigna a mano
        $solicitud->Estado_Solicitud = 'Rechazada';
        $solicitud->save();

        return back()->with('exito', 'Rechazaste la solicitud de ' . $solicitud->particular->Nombre . '.');
    }

    // PUT /solicitudes/{id}/elegir: concreta la adopción con este solicitante (RF:0052)
    public function elegir(string $id)
    {
        $solicitud = $this->buscarSolicitudRecibida($id);
        $mascota = $solicitud->adopcion;

        if ($solicitud->Estado_Solicitud != 'Pendiente' || $mascota->Estado_Adopcion != 'En adopción')
        {
            return back()->with('error', 'Solo se puede elegir una solicitud pendiente de una mascota En adopción.');
        }

        // Todo junto o nada: la solicitud, las demás, la publicación y la adopción
        DB::transaction(function () use ($solicitud, $mascota)
        {
            $solicitud->Estado_Solicitud = 'Adoptada';
            $solicitud->save();

            // Las demás Pendientes de la misma mascota pasan a Rechazada (las rechaza el sistema)
            SolicitudAdopcion::where('ID_Publicacion', $solicitud->ID_Publicacion)
                ->where('ID_Solicitud', '!=', $solicitud->ID_Solicitud)
                ->where('Estado_Solicitud', 'Pendiente')
                ->update(['Estado_Solicitud' => 'Rechazada']);

            // La publicación pasa a Adoptado: es definitivo, no vuelve a En adopción
            $mascota->Estado_Adopcion = 'Adoptado';
            $mascota->save();

            // Se registra la fecha de adopción (de ahí se cuentan los días del seguimiento)
            $adopcion = Adopcion::create([
                'ID_Solicitud' => $solicitud->ID_Solicitud,
                'Fecha_Adopcion' => now()->toDateString(),
            ]);

            // Y su seguimiento (RNE20): aviso el día 15 y límite el día 22, en días corridos
            Seguimiento::create([
                'ID_Adopcion' => $adopcion->ID_Adopcion,
                'Fecha_Aviso' => now()->addDays(15)->toDateString(),
                'Fecha_Limite' => now()->addDays(22)->toDateString(),
            ]);
        });

        return back()->with('exito', '¡Felicitaciones! La mascota fue adoptada por '
            . $solicitud->particular->Nombre . ' ' . $solicitud->particular->Apellido . '.');
    }

    // Busca una solicitud de una publicación del usuario que inició sesión; si es ajena, 403
    private function buscarSolicitudRecibida($id)
    {
        $solicitud = SolicitudAdopcion::find($id);

        if (!$solicitud)
        {
            abort(404);
        }

        if ($solicitud->adopcion->publicacion->ID_Usuario != session('id_usuario'))
        {
            abort(403, 'Solo quien publicó puede responder esta solicitud.');
        }

        return $solicitud;
    }

    // Busca una mascota En adopción y visible; si no existe o es una perdida o encontrada, 404 (RNE18)
    private function buscarEnAdopcion($id)
    {
        $publicacion = PublicacionAnimal::find($id);

        if (!$publicacion || $publicacion->Tipo_Publicacion != 'Adopción' || !$publicacion->esPublica())
        {
            abort(404);
        }

        return $publicacion;
    }

    // Reglas de RF:0049: devuelve el motivo por el que no se puede solicitar, o null si se puede.
    // (Que un Refugio no solicite ya lo controla el middleware rol:Particular de la ruta.)
    private function motivoParaNoSolicitar($publicacion)
    {
        if ($publicacion->ID_Usuario == session('id_usuario'))
        {
            return 'No podés solicitar la adopción de tu propia publicación.';
        }

        // Cuenta restringida por seguimiento incumplido (RF:0058)
        if (Usuario::estaRestringido(session('id_usuario')))
        {
            return Seguimiento::MENSAJE_RESTRINGIDA;
        }

        $yaPendiente = SolicitudAdopcion::where('ID_Publicacion', $publicacion->ID_Publicacion)
            ->where('ID_Usuario', session('id_usuario'))
            ->where('Estado_Solicitud', 'Pendiente')
            ->exists();

        if ($yaPendiente)
        {
            return 'Ya tenés una solicitud pendiente para esta mascota.';
        }

        return null;
    }
}
