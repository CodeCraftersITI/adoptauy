<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAnimal;
use Illuminate\Http\Request;

/**
 * Publicaciones en el panel del Administrador (rutas protegidas con rol:Administrador):
 *  - Listado de todas las publicaciones, de cualquier estado (RF:0004).
 *  - Consulta con filtros por fecha, tipo, especie y usuario (RF:0002), y por tipo de
 *    publicante: las de particulares (RF:0011) o las de refugios (RF:0012).
 *  - Baja: la publicación pasa a Archivado o Archivada (RF:0001, D68). Es la transición
 *    "Retiro de la publicación" de 13.2.2, que el Administrador también puede hacer.
 */
class AdminPublicacionController extends Controller
{
    private const POR_PAGINA = 20;

    // GET /admin/publicaciones?tipo=&especie=&publicante=&usuario=&desde=&hasta=
    public function index(Request $request)
    {
        $consulta = PublicacionAnimal::query();

        // Tipo: Adopción, Perdida o Encontrada (las dos últimas están en Publicacion_Perdida)
        if ($request->input('tipo') == 'Adopción')
        {
            $consulta->where('Tipo_Publicacion', 'Adopción');
        }
        if ($request->input('tipo') == 'Perdida' || $request->input('tipo') == 'Encontrada')
        {
            $tipo = $request->input('tipo');
            $consulta->whereHas('perdida', function ($perdida) use ($tipo)
            {
                $perdida->where('Tipo_Aviso', $tipo);
            });
        }

        if ($request->filled('especie'))
        {
            $consulta->where('Especie', $request->input('especie'));
        }

        // Publicante: Particular (RF:0011) o Refugio (RF:0012)
        if ($request->filled('publicante'))
        {
            $tipoPublicante = $request->input('publicante');
            $consulta->whereHas('usuario', function ($usuario) use ($tipoPublicante)
            {
                $usuario->where('Tipo_Usuario', $tipoPublicante);
            });
        }

        // Usuario: busca por correo, por nombre del particular o por nombre del refugio
        if ($request->filled('usuario'))
        {
            $texto = '%' . $request->input('usuario') . '%';
            $consulta->whereHas('usuario', function ($usuario) use ($texto)
            {
                $usuario->where('Email', 'like', $texto)
                    ->orWhereHas('particular', function ($particular) use ($texto)
                    {
                        $particular->where('Nombre', 'like', $texto)->orWhere('Apellido', 'like', $texto);
                    })
                    ->orWhereHas('refugio', function ($refugio) use ($texto)
                    {
                        $refugio->where('Nombre_Refugio', 'like', $texto);
                    });
            });
        }

        // Rango de fechas de publicación (la hora se ve en la tabla)
        if ($request->filled('desde'))
        {
            $consulta->whereDate('Fecha_Creacion', '>=', $request->input('desde'));
        }
        if ($request->filled('hasta'))
        {
            $consulta->whereDate('Fecha_Creacion', '<=', $request->input('hasta'));
        }

        $publicaciones = $consulta->orderBy('Fecha_Creacion', 'desc')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('admin.publicaciones', ['publicaciones' => $publicaciones]);
    }

    // PUT /admin/publicaciones/{id}/archivar: baja de la publicación (RF:0001)
    public function archivar(string $id)
    {
        $publicacion = PublicacionAnimal::find($id);

        if (!$publicacion)
        {
            abort(404);
        }

        // Solo las transiciones de 13.2.2: En adopción -> Archivado y Vigente -> Archivada
        if ($publicacion->Tipo_Publicacion == 'Adopción' && $publicacion->adopcion->Estado_Adopcion == 'En adopción')
        {
            $publicacion->adopcion->Estado_Adopcion = 'Archivado';
            $publicacion->adopcion->save();
        }
        elseif ($publicacion->Tipo_Publicacion == 'Perdida' && $publicacion->perdida->Estado_Perdida == 'Vigente')
        {
            $publicacion->perdida->Estado_Perdida = 'Archivada';
            $publicacion->perdida->save();
        }
        else
        {
            return back()->with('error', 'Solo se puede dar de baja una publicación En adopción o Vigente.');
        }

        return back()->with('exito', 'La publicación quedó archivada y ya no se ve en la consulta pública.');
    }
}
