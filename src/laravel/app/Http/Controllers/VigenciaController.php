<?php

namespace App\Http\Controllers;

use App\Models\PublicacionPerdida;

/**
 * Vigencia y cierre de mascotas perdidas y encontradas (RF:0047, RNE17, CP-14).
 * Transiciones de 13.2.2 que hace el publicante desde "Mi cuenta":
 *   Vigente o Archivada -> Vigente   renovar: vence 30 días después de la renovación
 *   Vigente -> Resuelta              la mascota volvió con su dueño (es definitivo)
 *   Vigente -> Archivada             retiro de la publicación
 * El paso Vigente -> Archivada por vencimiento lo hace la tarea diaria
 * (comando publicaciones:archivar-vencidas, a las 03:00).
 */
class VigenciaController extends Controller
{
    // PUT /publicaciones/{id}/renovar
    public function renovar(string $id)
    {
        $perdida = $this->buscarPropia($id);

        if (!in_array($perdida->Estado_Perdida, ['Vigente', 'Archivada']))
        {
            return back()->with('error', 'Solo se puede renovar una publicación vigente o archivada.');
        }

        // Estado_Perdida y Fecha_Vencimiento no están en $fillable: se asignan a mano
        $perdida->Estado_Perdida = 'Vigente';
        $perdida->Fecha_Vencimiento = now()->addDays(30)->toDateString();   // RNE17
        $perdida->save();

        return back()->with('exito', 'Renovaste la publicación de ' . $perdida->nombreVisible()
            . '. Ahora vence el ' . now()->addDays(30)->format('d/m/Y') . '.');
    }

    // PUT /publicaciones/{id}/resolver
    public function resolver(string $id)
    {
        $perdida = $this->buscarPropia($id);

        if ($perdida->Estado_Perdida != 'Vigente')
        {
            return back()->with('error', 'Solo se puede marcar como resuelta una publicación vigente.');
        }

        $perdida->Estado_Perdida = 'Resuelta';
        $perdida->save();

        return back()->with('exito', '¡Qué buena noticia! La publicación de ' . $perdida->nombreVisible() . ' quedó resuelta.');
    }

    // PUT /publicaciones/{id}/archivar
    public function archivar(string $id)
    {
        $perdida = $this->buscarPropia($id);

        if ($perdida->Estado_Perdida != 'Vigente')
        {
            return back()->with('error', 'Solo se puede archivar una publicación vigente.');
        }

        $perdida->Estado_Perdida = 'Archivada';
        $perdida->save();

        return back()->with('exito', 'Archivaste la publicación de ' . $perdida->nombreVisible() . '. La podés renovar cuando quieras.');
    }

    // Busca la perdida o encontrada y comprueba que sea del usuario que inició sesión:
    // solo el publicante puede cambiar el estado de su publicación (RNE03)
    private function buscarPropia($id)
    {
        $perdida = PublicacionPerdida::find($id);

        if (!$perdida)
        {
            abort(404);
        }

        if ($perdida->publicacion->ID_Usuario != session('id_usuario'))
        {
            abort(403, 'No podés cambiar una publicación de otra persona.');
        }

        return $perdida;
    }
}
