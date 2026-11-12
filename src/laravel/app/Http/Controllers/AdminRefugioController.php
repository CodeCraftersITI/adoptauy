<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAdopcion;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Refugios en el panel del Administrador (rutas protegidas con rol:Administrador):
 *  - Listado de refugios (RF:0009) y consulta por nombre, ubicación y estado (RF:0008).
 *  - Transiciones de la tabla de 13.2.1 que hace el Administrador:
 *      Validado -> Suspendido                 suspender
 *      Suspendido -> Validado                 levantar la suspensión
 *      Validado o Suspendido -> Dado de baja  baja lógica (RF:0006, D69): no puede iniciar sesión,
 *                                             sale del mapa y del listado, y sus publicaciones
 *                                             pasan a Archivado o Archivada. Es definitiva.
 * Las solicitudes Pendientes se validan o rechazan desde AdminController (RF:0005, RF:0044).
 */
class AdminRefugioController extends Controller
{
    private const POR_PAGINA = 20;

    // GET /admin/refugios?nombre=&ubicacion=&estado=
    public function index(Request $request)
    {
        $consulta = Refugio::query();

        if ($request->filled('nombre'))
        {
            $consulta->where('Nombre_Refugio', 'like', '%' . $request->input('nombre') . '%');
        }

        if ($request->filled('ubicacion'))
        {
            $consulta->where('Ubicacion', 'like', '%' . $request->input('ubicacion') . '%');
        }

        if ($request->filled('estado'))
        {
            $consulta->where('Estado_Validacion', $request->input('estado'));
        }

        $refugios = $consulta->orderBy('Nombre_Refugio')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('admin.refugios', ['refugios' => $refugios]);
    }

    // PUT /admin/refugios/{id}/suspender
    public function suspender(string $id)
    {
        $refugio = $this->buscar($id);

        if ($refugio->Estado_Validacion != 'Validado')
        {
            return back()->with('error', 'Solo se puede suspender un refugio Validado.');
        }

        $refugio->Estado_Validacion = 'Suspendido';
        $refugio->save();

        return back()->with('exito', $refugio->Nombre_Refugio . ' quedó suspendido: no puede iniciar sesión y sale del mapa.');
    }

    // PUT /admin/refugios/{id}/levantar
    public function levantarSuspension(string $id)
    {
        $refugio = $this->buscar($id);

        if ($refugio->Estado_Validacion != 'Suspendido')
        {
            return back()->with('error', 'Solo se puede levantar la suspensión de un refugio Suspendido.');
        }

        $refugio->Estado_Validacion = 'Validado';
        $refugio->save();

        return back()->with('exito', 'Se levantó la suspensión de ' . $refugio->Nombre_Refugio . '.');
    }

    // PUT /admin/refugios/{id}/baja: baja lógica (RF:0006)
    public function darDeBaja(string $id)
    {
        $refugio = $this->buscar($id);

        if (!in_array($refugio->Estado_Validacion, ['Validado', 'Suspendido']))
        {
            return back()->with('error', 'Solo se puede dar de baja un refugio Validado o Suspendido.');
        }

        // El refugio, su cuenta y sus publicaciones cambian juntos: o todo o nada
        DB::transaction(function () use ($refugio)
        {
            $refugio->Estado_Validacion = 'Dado de baja';
            $refugio->save();

            $refugio->usuario->Estado_Usuario = 'Baja';
            $refugio->usuario->save();

            // Sus publicaciones activas se archivan (las Adoptadas y Resueltas quedan como están)
            PublicacionAdopcion::where('Estado_Adopcion', 'En adopción')
                ->whereHas('publicacion', function ($publicacion) use ($refugio)
                {
                    $publicacion->where('ID_Usuario', $refugio->ID_Usuario);
                })
                ->update(['Estado_Adopcion' => 'Archivado']);

            PublicacionPerdida::where('Estado_Perdida', 'Vigente')
                ->whereHas('publicacion', function ($publicacion) use ($refugio)
                {
                    $publicacion->where('ID_Usuario', $refugio->ID_Usuario);
                })
                ->update(['Estado_Perdida' => 'Archivada']);
        });

        return back()->with('exito', $refugio->Nombre_Refugio . ' quedó dado de baja y sus publicaciones se archivaron.');
    }

    private function buscar($id)
    {
        $refugio = Refugio::find($id);

        if (!$refugio)
        {
            abort(404);
        }

        return $refugio;
    }
}
