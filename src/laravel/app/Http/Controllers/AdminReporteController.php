<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAnimal;
use App\Models\Reporte;
use App\Models\Usuario;
use Illuminate\Http\Request;

// Resolución de reportes por el Administrador (RF:0060, RF:0006)
class AdminReporteController extends Controller
{
    // GET /admin/reportes: publicaciones y usuarios con reportes pendientes
    public function index()
    {
        $soloPendientes = function ($reporte)
        {
            $reporte->where('Estado_Reporte', 'Pendiente');
        };

        return view('admin.reportes', [
            'publicaciones' => PublicacionAnimal::whereHas('reportes', $soloPendientes)->get(),
            'usuarios' => Usuario::whereHas('reportesRecibidos', $soloPendientes)->get(),
        ]);
    }

    // PUT /admin/reportes/publicacion/{id}  medida = desestimar | suspension
    public function resolverPublicacion(Request $request, string $id)
    {
        $publicacion = PublicacionAnimal::find($id);

        if (!$publicacion)
        {
            abort(404);
        }

        $request->validate(['medida' => 'required|in:desestimar,suspension']);

        if ($request->input('medida') == 'desestimar')
        {
            $this->cerrarReportes($publicacion->reportes(), 'Desestimado', null);

            // Si ya no alcanza el umbral, vuelve a ser visible (RF:0060)
            if ($publicacion->Estado_Moderacion == 'En revisión'
                && Reporte::cantidadSobrePublicacion($publicacion->ID_Publicacion) < Reporte::UMBRAL_PUBLICACION)
            {
                $publicacion->Estado_Moderacion = 'Visible';
                $publicacion->save();
            }

            return back()->with('exito', 'Desestimaste los reportes de la publicación.');
        }

        $this->cerrarReportes($publicacion->reportes(), 'Confirmado', 'Suspensión');

        $publicacion->Estado_Moderacion = 'Suspendida';
        $publicacion->save();

        // Con 3 publicaciones suspendidas, la cuenta del particular pasa a revisión (RF:0061)
        Reporte::revisarParticular($publicacion->usuario);

        return back()->with('exito', 'La publicación quedó suspendida y no se ve en la consulta pública.');
    }

    // PUT /admin/reportes/usuario/{id}  medida = desestimar | aviso | suspension | baja
    public function resolverUsuario(Request $request, string $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario || $usuario->Tipo_Usuario == 'Administrador')
        {
            abort(404);
        }

        $request->validate(['medida' => 'required|in:desestimar,aviso,suspension,baja']);
        $medida = $request->input('medida');

        if ($medida == 'desestimar')
        {
            $this->cerrarReportes($usuario->reportesRecibidos(), 'Desestimado', null);

            return back()->with('exito', 'Desestimaste los reportes. Si la cuenta estaba en revisión, la podés restituir desde su pestaña.');
        }

        $nombres = ['aviso' => 'Aviso', 'suspension' => 'Suspensión', 'baja' => 'Baja lógica'];
        $this->cerrarReportes($usuario->reportesRecibidos(), 'Confirmado', $nombres[$medida]);

        // El aviso solo queda registrado; la suspensión y la baja cambian el estado de la cuenta
        if ($usuario->Tipo_Usuario == 'Refugio')
        {
            if ($medida == 'suspension' && $usuario->refugio->Estado_Validacion == 'Validado')
            {
                $usuario->refugio->Estado_Validacion = 'Suspendido';
                $usuario->refugio->save();
            }

            if ($medida == 'baja' && in_array($usuario->refugio->Estado_Validacion, ['Validado', 'Suspendido']))
            {
                $usuario->refugio->darDeBaja();
            }
        }
        else
        {
            if ($medida == 'suspension')
            {
                $usuario->Estado_Usuario = 'Suspendido';
                $usuario->save();
            }

            if ($medida == 'baja')
            {
                $usuario->Estado_Usuario = 'Baja';
                $usuario->save();
            }
        }

        return back()->with('exito', 'Se registró la medida: ' . $nombres[$medida] . '.');
    }

    // Pasa los reportes Pendientes al estado elegido, con la medida y la fecha de resolución
    private function cerrarReportes($reportes, $estado, $medida)
    {
        $reportes->where('Estado_Reporte', 'Pendiente')->update([
            'Estado_Reporte' => $estado,
            'Medida' => $medida,
            'Fecha_Resolucion' => now(),
        ]);
    }
}
