<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use Illuminate\Http\Request;

/**
 * Particulares en el panel del Administrador (rutas protegidas con rol:Administrador):
 *  - Listado con búsqueda por nombre, apellido o correo y por estado (RF:0014).
 *  - Baja lógica: Estado_Usuario pasa a Baja y no puede volver a iniciar sesión (RF:0010, D23).
 *    Nunca se borra la fila (regla 7 del equipo).
 * La CI no se muestra en el panel: nunca se muestra a otros usuarios (RNF:0013).
 */
class AdminParticularController extends Controller
{
    private const POR_PAGINA = 20;

    // GET /admin/particulares?buscar=&estado=
    public function index(Request $request)
    {
        $consulta = Particular::query();

        if ($request->filled('buscar'))
        {
            $texto = '%' . $request->input('buscar') . '%';
            $consulta->where(function ($particular) use ($texto)
            {
                $particular->where('Nombre', 'like', $texto)
                    ->orWhere('Apellido', 'like', $texto)
                    ->orWhereHas('usuario', function ($usuario) use ($texto)
                    {
                        $usuario->where('Email', 'like', $texto);
                    });
            });
        }

        if ($request->filled('estado'))
        {
            $estado = $request->input('estado');
            $consulta->whereHas('usuario', function ($usuario) use ($estado)
            {
                $usuario->where('Estado_Usuario', $estado);
            });
        }

        $particulares = $consulta->orderBy('Apellido')
            ->orderBy('Nombre')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('admin.particulares', ['particulares' => $particulares]);
    }

    // PUT /admin/particulares/{id}/baja: baja lógica (RF:0010)
    public function darDeBaja(string $id)
    {
        $particular = Particular::find($id);

        if (!$particular)
        {
            abort(404);
        }

        if ($particular->usuario->Estado_Usuario == 'Baja')
        {
            return back()->with('error', 'Esa cuenta ya está dada de baja.');
        }

        // Estado_Usuario no está en $fillable: se asigna a mano
        $particular->usuario->Estado_Usuario = 'Baja';
        $particular->usuario->save();

        return back()->with('exito', 'La cuenta de ' . $particular->Nombre . ' ' . $particular->Apellido . ' quedó dada de baja.');
    }
}
