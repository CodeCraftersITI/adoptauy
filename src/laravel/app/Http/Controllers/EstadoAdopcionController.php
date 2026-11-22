<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAdopcion;
use App\Models\Seguimiento;
use App\Models\Usuario;

/**
 * Estados de una mascota en adopción que cambia su publicante desde "Mi cuenta"
 * (RF:0018 Particular y RF:0025 Refugio: baja de la publicación; RF:0045: cambio de estado).
 * Transiciones de 13.2.2 que hace el publicante:
 *   En adopción -> Archivado   retiro de la publicación (baja lógica: no se borra)
 *   Archivado -> En adopción   reactivación
 * El paso a Adoptado se hace eligiendo al adoptante (RF:0052) y es definitivo.
 * El Administrador archiva desde su panel (RF:0001).
 */
class EstadoAdopcionController extends Controller
{
    // PUT /adopciones/{id}/archivar
    public function archivar(string $id)
    {
        $adopcion = $this->buscarPropia($id);

        if ($adopcion->Estado_Adopcion != 'En adopción')
        {
            return back()->with('error', 'Solo se puede archivar una mascota que está En adopción.');
        }

        // Estado_Adopcion no está en $fillable: se asigna a mano
        $adopcion->Estado_Adopcion = 'Archivado';
        $adopcion->save();

        return back()->with('exito', 'Archivaste la publicación: ya no se ve en el sitio. La podés reactivar cuando quieras.');
    }

    // PUT /adopciones/{id}/reactivar
    public function reactivar(string $id)
    {
        $adopcion = $this->buscarPropia($id);

        if ($adopcion->Estado_Adopcion != 'Archivado')
        {
            return back()->with('error', 'Solo se puede reactivar una publicación archivada.');
        }

        // Reactivar es volver a publicar: con un seguimiento incumplido no se puede (RF:0058)
        if (Usuario::estaRestringido(session('id_usuario')))
        {
            return back()->with('error', Seguimiento::MENSAJE_RESTRINGIDA);
        }

        $adopcion->Estado_Adopcion = 'En adopción';
        $adopcion->save();

        return back()->with('exito', 'Reactivaste la publicación: vuelve a estar En adopción.');
    }

    // Busca la mascota en adopción y comprueba que sea del usuario que inició sesión:
    // solo el publicante puede cambiar el estado de su publicación (RNE03)
    private function buscarPropia($id)
    {
        $adopcion = PublicacionAdopcion::find($id);

        if (!$adopcion)
        {
            abort(404);
        }

        if ($adopcion->publicacion->ID_Usuario != session('id_usuario'))
        {
            abort(403, 'No podés cambiar una publicación de otra persona.');
        }

        return $adopcion;
    }
}
