<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use App\Rules\CedulaUruguaya;
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

    // GET /admin/particulares/{id}/editar (RF:0013)
    public function editar(string $id)
    {
        return view('admin.editar-particular', ['particular' => $this->buscar($id)]);
    }

    // PUT /admin/particulares/{id}: modifica los atributos del Particular (RF:0013):
    // cédula, nombre, apellido y teléfono, con las mismas reglas que el registro
    public function actualizar(Request $request, string $id)
    {
        $particular = $this->buscar($id);

        $request->merge([
            'ci' => str_replace(['.', '-', ' '], '', $request->input('ci') ?? ''),
            'telefono' => str_replace(' ', '', $request->input('telefono') ?? ''),
        ]);

        // La cédula es opcional: el Administrador no la ve (RNF:0013) y solo la escribe para
        // corregirla. Si la escribe, tiene que ser válida y única (RNE23), sin contar la de este particular
        $request->validate([
            'ci' => ['nullable', new CedulaUruguaya, 'unique:Particular,CI,' . $particular->ID_Usuario . ',ID_Usuario'],
        ] + Particular::REGLAS, [
            'ci.unique' => 'Ya existe otra cuenta con esa cédula.',
        ] + Particular::MENSAJES);

        if ($request->filled('ci'))
        {
            $particular->CI = $request->input('ci');
        }
        $particular->Nombre = $request->input('nombre');
        $particular->Apellido = $request->input('apellido');
        $particular->Telefono = $request->input('telefono');
        $particular->save();

        return redirect('/admin/particulares')->with('exito', 'Guardaste los datos de ' . $particular->Nombre . ' ' . $particular->Apellido . '.');
    }

    private function buscar($id)
    {
        $particular = Particular::find($id);

        if (!$particular)
        {
            abort(404);
        }

        return $particular;
    }

    // PUT /admin/particulares/{id}/restituir: la cuenta vuelve a Activo después de una revisión,
    // una suspensión o una inhabilitación por reportes (13.2.5, RF:0060). La Baja es definitiva
    public function restituir(string $id)
    {
        $particular = Particular::find($id);

        if (!$particular)
        {
            abort(404);
        }

        if (!in_array($particular->usuario->Estado_Usuario, ['En revisión', 'Suspendido', 'Inhabilitado']))
        {
            return back()->with('error', 'Solo se restituye una cuenta en revisión, suspendida o inhabilitada.');
        }

        $particular->usuario->Estado_Usuario = 'Activo';
        $particular->usuario->save();

        return back()->with('exito', 'La cuenta de ' . $particular->Nombre . ' ' . $particular->Apellido . ' volvió a estar activa.');
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
