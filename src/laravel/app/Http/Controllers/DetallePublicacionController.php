<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAnimal;
use App\Models\SolicitudAdopcion;

// Detalle de una publicación (RF:0040, RF:0048, RF:0065)
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

        // Si no es pública, solo la ven su publicante y el Administrador
        $esPublicante = session('id_usuario') == $publicacion->ID_Usuario;
        $esAdministrador = session('tipo') == 'Administrador';

        if (!$publicacion->esPublica() && !$esPublicante && !$esAdministrador)
        {
            abort(404);
        }

        // Para el botón Solicitar adopción (RF:0049)
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
