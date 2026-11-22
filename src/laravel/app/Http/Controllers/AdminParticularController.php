<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use App\Rules\CedulaUruguaya;
use Illuminate\Http\Request;

// Particulares en el panel del Administrador (RF:0014, RF:0010)
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

    // PUT /admin/particulares/{id} (RF:0013)
    public function actualizar(Request $request, string $id)
    {
        $particular = $this->buscar($id);

        $request->merge([
            'ci' => str_replace(['.', '-', ' '], '', $request->input('ci') ?? ''),
            'telefono' => str_replace(' ', '', $request->input('telefono') ?? ''),
        ]);

        // La cédula es opcional
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

    // PUT /admin/particulares/{id}/restituir (RF:0060)
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
