<?php

namespace App\Http\Controllers;

use App\Mail\RefugioDadoDeAlta;
use App\Models\Refugio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Panel de administración (todas sus rutas están protegidas con rol:Administrador).
 * Por ahora: solicitudes de registro de refugios (RF:0005 alta, RF:0044 rechazo).
 * Las transiciones válidas son las de la tabla de 13.2.1: solo una solicitud
 * "Pendiente de validación" puede pasar a "Validado" o a "Rechazado".
 */
class AdminController extends Controller
{
    // GET /admin: lista de solicitudes pendientes, la más vieja primero (plazo de 5 días hábiles, RNE06)
    public function index()
    {
        $pendientes = Refugio::where('Estado_Validacion', 'Pendiente de validación')
            ->orderBy('Fecha_Solicitud')
            ->get();

        return view('admin.index', ['pendientes' => $pendientes]);
    }

    // GET /admin/refugios/{id}/documento/{tipo}: muestra el certificado o el comprobante
    public function verDocumento(string $id, string $tipo)
    {
        $refugio = Refugio::find($id);

        if (!$refugio)
        {
            abort(404);
        }

        if ($tipo == 'certificado')
        {
            return Storage::response($refugio->Certificado_Personeria);
        }

        if ($tipo == 'comprobante')
        {
            return Storage::response($refugio->Comprobante_Domicilio);
        }

        abort(404);
    }

    // PUT /admin/refugios/{id}/validar: alta del refugio (RF:0005)
    public function validar(string $id)
    {
        $refugio = Refugio::find($id);

        if (!$refugio || $refugio->Estado_Validacion != 'Pendiente de validación')
        {
            return back()->with('error', 'Esa solicitud ya no está pendiente.');
        }

        // Estado_Validacion no está en $fillable: se asigna a mano y se guarda con save()
        $refugio->Estado_Validacion = 'Validado';
        $refugio->Fecha_Resolucion = now();
        $refugio->save();

        // Correo de alta (RF:0063). Si falla el correo, el alta igual queda hecha (RNF:0010)
        try
        {
            Mail::to($refugio->usuario->Email)->send(new RefugioDadoDeAlta($refugio));
        }
        catch (\Exception $e)
        {
            report($e);
        }

        return back()->with('exito', $refugio->Nombre_Refugio . ' quedó validado y ya puede iniciar sesión.');
    }

    // PUT /admin/refugios/{id}/rechazar: rechazo con motivo obligatorio (RF:0044, RNE05)
    public function rechazar(Request $request, string $id)
    {
        $refugio = Refugio::find($id);

        if (!$refugio || $refugio->Estado_Validacion != 'Pendiente de validación')
        {
            return back()->with('error', 'Esa solicitud ya no está pendiente.');
        }

        $motivo = trim($request->input('motivo') ?? '');

        if ($motivo == '')
        {
            return back()->with('error', 'Para rechazar la solicitud de ' . $refugio->Nombre_Refugio . ' tenés que escribir el motivo.');
        }

        if (mb_strlen($motivo) > 500)
        {
            return back()->with('error', 'El motivo puede tener hasta 500 caracteres.');
        }

        $refugio->Estado_Validacion = 'Rechazado';
        $refugio->Motivo_Rechazo = $motivo;
        $refugio->Fecha_Resolucion = now();
        $refugio->save();

        return back()->with('exito', 'Se rechazó la solicitud de ' . $refugio->Nombre_Refugio . '.');
    }
}
