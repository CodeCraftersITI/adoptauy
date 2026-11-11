<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAnimal;
use App\Models\SolicitudAdopcion;

/**
 * Detalle de una publicación (pantalla «Detalle de mascota» del prototipo).
 * Consulta pública de una mascota en adopción, perdida o encontrada (RF:0040, RF:0048, RF:0065):
 * todas las fotos y todos los datos. Es el ejemplo show($id) con find() del teórico.
 */
class DetallePublicacionController extends Controller
{
    // GET /publicaciones/{id}
    public function show(string $id)
    {
        $publicacion = PublicacionAnimal::find($id);

        if (!$publicacion)
        {
            abort(404);
        }

        // Si no es pública (adoptada, archivada, resuelta, vencida u oculta por reportes),
        // solo la ven su publicante y el Administrador; para el resto no existe
        $esPublicante = session('id_usuario') == $publicacion->ID_Usuario;
        $esAdministrador = session('tipo') == 'Administrador';

        if (!$publicacion->esPublica() && !$esPublicante && !$esAdministrador)
        {
            abort(404);
        }

        // Para el botón Solicitar adopción (RF:0049): ¿ya tiene una solicitud Pendiente sobre esta mascota?
        $solicitudPendiente = false;
        if ($publicacion->Tipo_Publicacion == 'Adopción' && session('tipo') == 'Particular')
        {
            $solicitudPendiente = SolicitudAdopcion::where('ID_Publicacion', $publicacion->ID_Publicacion)
                ->where('ID_Usuario', session('id_usuario'))
                ->where('Estado_Solicitud', 'Pendiente')
                ->exists();
        }

        return view('publicaciones.detalle', [
            'publicacion' => $publicacion,
            'esPublicante' => $esPublicante,
            'solicitudPendiente' => $solicitudPendiente,
        ]);
    }
}
