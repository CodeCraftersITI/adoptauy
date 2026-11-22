<?php

namespace App\Http\Controllers;

use App\Models\Seguimiento;
use Illuminate\Http\Request;

// Seguimiento posterior a la adopción (RF:0056, RF:0058, RF:0057)
class SeguimientoController extends Controller
{
    // GET /seguimientos: mis adopciones (como adoptante) y las de mis publicaciones (como publicante)
    public function index()
    {
        $comoAdoptante = Seguimiento::whereHas('adopcion.solicitud', function ($solicitud)
            {
                $solicitud->where('ID_Usuario', session('id_usuario'));
            })
            ->orderBy('Fecha_Aviso', 'desc')
            ->get();

        $comoPublicante = Seguimiento::whereHas('adopcion.solicitud.adopcion.publicacion', function ($publicacion)
            {
                $publicacion->where('ID_Usuario', session('id_usuario'));
            })
            ->orderBy('Fecha_Aviso', 'desc')
            ->get();

        return view('seguimientos.index', [
            'comoAdoptante' => $comoAdoptante,
            'comoPublicante' => $comoPublicante,
        ]);
    }

    // POST /seguimientos/{id}/foto: el adoptante envía la foto (RF:0056)
    public function enviarFoto(Request $request, string $id)
    {
        $seguimiento = Seguimiento::find($id);

        if (!$seguimiento)
        {
            abort(404);
        }

        if ($seguimiento->adopcion->solicitud->ID_Usuario != session('id_usuario'))
        {
            abort(403, 'Solo quien adoptó puede enviar la foto.');
        }

        if (!$seguimiento->puedeEnviarFoto())
        {
            return back()->with('error', 'Por ahora no se puede enviar una foto para esta adopción.');
        }

        // Una foto, con el mismo formato y tamaño que las de las publicaciones
        $request->validate([
            'foto' => 'required|file|mimes:jpg,jpeg,png,webp|max:8192',
        ], [
            'foto.required' => 'Elegí una foto.',
            'foto.file' => 'No se pudo subir la foto.',
            'foto.mimes' => 'La foto tiene que ser JPG, PNG o WEBP.',
            'foto.max' => 'La foto puede pesar hasta 8 MB.',
        ]);

        // URL_Foto, Fecha_Envio y Estado_Evidencia no están en $fillable: se asignan a mano
        $seguimiento->URL_Foto = $request->file('foto')->store('seguimientos', 'public');
        $seguimiento->Fecha_Envio = now();
        $seguimiento->Estado_Evidencia = 'Enviada';
        $seguimiento->save();

        return back()->with('exito', '¡Gracias! Enviaste la foto y quien publicó ya la puede ver.');
    }

    // PUT /seguimientos/{id}/aprobar (RF:0057)
    public function aprobar(string $id)
    {
        return $this->revisar($id, 'Aprobada', 'Aprobaste la foto del seguimiento.');
    }

    // PUT /seguimientos/{id}/rechazar (RF:0057): el adoptante tiene que enviar otra
    public function rechazar(string $id)
    {
        return $this->revisar($id, 'Rechazada', 'Rechazaste la foto: el adoptante tiene que enviar otra.');
    }

    // Aprobar y rechazar son iguales salvo el estado al que pasa
    private function revisar($id, $estado, $mensaje)
    {
        $seguimiento = Seguimiento::find($id);

        if (!$seguimiento)
        {
            abort(404);
        }

        if ($seguimiento->adopcion->solicitud->adopcion->publicacion->ID_Usuario != session('id_usuario'))
        {
            abort(403, 'Solo quien publicó puede revisar la foto.');
        }

        if ($seguimiento->Estado_Evidencia != 'Enviada')
        {
            return back()->with('error', 'Solo se puede revisar una foto enviada.');
        }

        $seguimiento->Estado_Evidencia = $estado;
        $seguimiento->save();

        return back()->with('exito', $mensaje);
    }
}
