<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAnimal;
use App\Models\Reporte;
use App\Models\Usuario;
use Illuminate\Http\Request;

/**
 * Reportar una publicación, a un particular o a un refugio (RF:0059, CP-30), con un motivo
 * de la lista de 13.2.5 que corresponde. Lo puede hacer un usuario registrado (rol:Particular,Refugio).
 * Al guardarlo se aplican los umbrales automáticos (RF:0061). RNE22: un usuario no se reporta
 * a sí mismo ni reporta dos veces lo mismo.
 * Qué se reporta llega en la dirección: /reportar?publicacion=ID o /reportar?usuario=ID
 */
class ReporteController extends Controller
{
    // GET /reportar?publicacion=ID o ?usuario=ID
    public function crear(Request $request)
    {
        $objeto = $this->objetoReportado($request);

        $motivo = $this->motivoParaNoReportar($objeto);
        if ($motivo)
        {
            return redirect($objeto['volver'])->with('error', $motivo);
        }

        return view('reportes.crear', [
            'objeto' => $objeto,
            'motivos' => Reporte::MOTIVOS[$objeto['tipo']],
        ]);
    }

    // POST /reportar
    public function guardar(Request $request)
    {
        $objeto = $this->objetoReportado($request);

        $motivo = $this->motivoParaNoReportar($objeto);
        if ($motivo)
        {
            return redirect($objeto['volver'])->with('error', $motivo);
        }

        // El motivo tiene que ser uno de la lista de ese tipo; "Otro" pide el texto
        $request->validate([
            'motivo' => 'required|in:' . implode(',', Reporte::MOTIVOS[$objeto['tipo']]),
            'detalle' => 'required_if:motivo,Otro|nullable|max:500',
        ], [
            'motivo.required' => 'Elegí un motivo.',
            'motivo.in' => 'Elegí un motivo de la lista.',
            'detalle.required_if' => 'Contá brevemente qué pasa.',
            'detalle.max' => 'El detalle puede tener hasta 500 caracteres.',
        ]);

        // ID_Usuario_Reportante no está en $fillable: el reportante es siempre quien inició sesión
        $reporte = new Reporte([
            'ID_Publicacion_Reportada' => $objeto['publicacion'] ? $objeto['publicacion']->ID_Publicacion : null,
            'ID_Usuario_Reportado' => $objeto['usuario'] ? $objeto['usuario']->ID_Usuario : null,
            'Motivo' => $request->input('motivo'),
            'Detalle' => $request->input('motivo') == 'Otro' ? $request->input('detalle') : null,
        ]);
        $reporte->ID_Usuario_Reportante = session('id_usuario');
        $reporte->save();

        // Medida automática si se alcanzó un umbral (RF:0061)
        $reporte->aplicarUmbrales();

        return redirect($objeto['volver'])->with('exito', 'Gracias por avisar. El Administrador va a revisar el reporte.');
    }

    // Arma los datos de lo que se reporta: tipo (Publicación, Particular o Refugio), la
    // publicación o el usuario, un texto para mostrar y a dónde volver. Si no existe, 404
    private function objetoReportado(Request $request)
    {
        if ($request->filled('publicacion'))
        {
            $publicacion = PublicacionAnimal::find($request->input('publicacion'));

            if (!$publicacion)
            {
                abort(404);
            }

            return [
                'tipo' => 'Publicación',
                'publicacion' => $publicacion,
                'usuario' => null,
                'descripcion' => $publicacion->tipoVisible() . ': ' . $publicacion->Especie . ' · ' . $publicacion->Raza,
                'volver' => '/publicaciones/' . $publicacion->ID_Publicacion,
            ];
        }

        $usuario = Usuario::find($request->input('usuario'));

        // Solo se reporta a un particular o a un refugio, nunca al Administrador
        if (!$usuario || $usuario->Tipo_Usuario == 'Administrador')
        {
            abort(404);
        }

        if ($usuario->Tipo_Usuario == 'Refugio')
        {
            return [
                'tipo' => 'Refugio',
                'publicacion' => null,
                'usuario' => $usuario,
                'descripcion' => 'Refugio ' . $usuario->refugio->Nombre_Refugio,
                'volver' => '/refugios/' . $usuario->ID_Usuario,
            ];
        }

        // De un particular solo se muestra el nombre (nunca la cédula, RNF:0013)
        return [
            'tipo' => 'Particular',
            'publicacion' => null,
            'usuario' => $usuario,
            'descripcion' => $usuario->particular->Nombre . ' ' . $usuario->particular->Apellido,
            'volver' => $this->paginaDeVuelta($request),
        ];
    }

    // Un particular no tiene página propia: se vuelve a la página desde donde se lo reportó
    // (?volver=/...). Solo se aceptan direcciones del propio sitio, que empiezan con una sola "/"
    private function paginaDeVuelta(Request $request)
    {
        $volver = $request->input('volver', '');

        if (str_starts_with($volver, '/') && !str_starts_with($volver, '//'))
        {
            return $volver;
        }

        return '/cuenta';
    }

    // RNE22: devuelve el motivo por el que no se puede reportar, o null si se puede
    private function motivoParaNoReportar($objeto)
    {
        $yo = session('id_usuario');

        if (($objeto['usuario'] && $objeto['usuario']->ID_Usuario == $yo)
            || ($objeto['publicacion'] && $objeto['publicacion']->ID_Usuario == $yo))
        {
            return 'No podés reportarte a vos mismo ni a tus publicaciones.';
        }

        $consulta = Reporte::where('ID_Usuario_Reportante', $yo);

        if ($objeto['publicacion'])
        {
            $consulta->where('ID_Publicacion_Reportada', $objeto['publicacion']->ID_Publicacion);
        }
        else
        {
            $consulta->where('ID_Usuario_Reportado', $objeto['usuario']->ID_Usuario);
        }

        if ($consulta->exists())
        {
            return 'Ya enviaste un reporte sobre esto.';
        }

        return null;
    }
}
