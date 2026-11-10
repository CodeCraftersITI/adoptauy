<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAnimal;

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

        return view('publicaciones.detalle', [
            'publicacion' => $publicacion,
            'esPublicante' => $esPublicante,
        ]);
    }
}
