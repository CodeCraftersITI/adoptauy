<?php

namespace App\Http\Controllers;

use App\Mail\SolicitudAdopcionRecibida;
use App\Models\Particular;
use App\Models\PublicacionAnimal;
use App\Models\SolicitudAdopcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * Solicitudes de adopción (13.2.3):
 *  - Solicitar adopción (RF:0049, CP-22): solo un Particular (middleware rol:Particular).
 *  - Mis solicitudes enviadas (RF:0054, CP-26).
 *  - Solicitudes recibidas por el publicante (RF:0050, CP-23).
 * Rechazar y concretar la adopción (RF:0051, RF:0052) se agregan en el paso siguiente.
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
