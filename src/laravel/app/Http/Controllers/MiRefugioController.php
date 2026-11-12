<?php

namespace App\Http\Controllers;

use App\Models\CuentaBancaria;
use App\Models\Refugio;
use Illuminate\Http\Request;

/**
 * "Mi refugio": lo que el refugio administra de su perfil público (protegido con rol:Refugio).
 *  - Ubicación en el mapa: el refugio toca el mapa y se guardan la latitud y la longitud (RF:0038).
 *  - Cuentas bancarias para donaciones: agregar (RF:0035), ver (RF:0036) y quitar (RF:0037).
 * Solo un refugio Validado publica cuentas (RNE04). Cada refugio maneja solo las suyas:
 * el ID_Usuario sale de la sesión, nunca del formulario (CP-21).
 */
class MiRefugioController extends Controller
{
    // GET /mi-refugio
    public function index()
    {
        return view('refugios.gestion', [
            'refugio' => $this->refugioDeLaSesion(),
        ]);
    }

    // PUT /mi-refugio/ubicacion
    public function guardarUbicacion(Request $request)
    {
        // Rango aproximado del departamento de Montevideo, con un poco de margen
        $request->validate([
            'latitud' => 'required|numeric|between:-35,-34.6',
            'longitud' => 'required|numeric|between:-56.5,-55.9',
        ], [
            'latitud.required' => 'Tocá el mapa para marcar dónde está el refugio.',
            'longitud.required' => 'Tocá el mapa para marcar dónde está el refugio.',
            'latitud.between' => 'La ubicación tiene que estar en Montevideo.',
            'longitud.between' => 'La ubicación tiene que estar en Montevideo.',
        ]);

        $refugio = $this->refugioDeLaSesion();
        $refugio->Latitud = $request->latitud;
        $refugio->Longitud = $request->longitud;
        $refugio->save();

        return redirect('/mi-refugio')->with('exito', 'Guardaste la ubicación del refugio. Ya aparece en el mapa.');
    }

    // POST /mi-refugio/cuentas
    public function guardarCuenta(Request $request)
    {
        $request->validate([
            'banco' => 'required|max:60',
            'tipo_cuenta' => 'required|max:60',
            'numero_cuenta' => 'required|max:30',
            'titular' => 'required|max:120',
        ]);

        $refugio = $this->refugioDeLaSesion();

        // El número de la cuenta sigue al último que cargó este refugio (clave compuesta)
        $siguiente = CuentaBancaria::where('ID_Usuario', $refugio->ID_Usuario)->max('ID_Cuenta_Banco') + 1;

        $cuenta = new CuentaBancaria([
            'Banco' => $request->banco,
            'Tipo_Cuenta' => $request->tipo_cuenta,
            'Numero_Cuenta' => $request->numero_cuenta,
            'Titular' => $request->titular,
        ]);
        $cuenta->ID_Usuario = $refugio->ID_Usuario;
        $cuenta->ID_Cuenta_Banco = $siguiente;
        $cuenta->save();

        return redirect('/mi-refugio')->with('exito', 'La cuenta ya se ve en el perfil público del refugio.');
    }

    // DELETE /mi-refugio/cuentas/{numero}
    public function eliminarCuenta(string $numero)
    {
        $refugio = $this->refugioDeLaSesion();

        // Se busca por las dos columnas de la clave: así un refugio no puede borrar la cuenta de otro
        $borradas = CuentaBancaria::where('ID_Usuario', $refugio->ID_Usuario)
            ->where('ID_Cuenta_Banco', $numero)
            ->delete();

        if ($borradas == 0)
        {
            abort(404);
        }

        return redirect('/mi-refugio')->with('exito', 'Quitaste la cuenta del perfil del refugio.');
    }

    // El refugio que inició sesión; si no está Validado no puede administrar su perfil (RNE04)
    private function refugioDeLaSesion()
    {
        $refugio = Refugio::find(session('id_usuario'));

        if (!$refugio || $refugio->Estado_Validacion != 'Validado')
        {
            abort(403);
        }

        return $refugio;
    }
}
