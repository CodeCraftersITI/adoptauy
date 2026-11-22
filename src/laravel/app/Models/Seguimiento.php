<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Seguimiento posterior a la adopción (tabla Seguimiento, 15.2.1): uno por adopción (RNE20).
 * Plazos en días corridos desde la fecha de adopción (D71):
 *   día 15 (Fecha_Aviso): se avisa por correo al adoptante y puede enviar la foto (RF:0055, RF:0056)
 *   día 22 (Fecha_Limite): si no hay foto, su cuenta queda restringida (RF:0058)
 * Estado_Evidencia: 'Pendiente', 'Enviada', 'Aprobada' o 'Rechazada'.
 */
class Seguimiento extends Model
{
    protected $table = 'Seguimiento';
    protected $primaryKey = 'ID_Adopcion';
    public $incrementing = false;
    public $timestamps = false;

    // Mensaje cuando la cuenta está restringida (RF:0058)
    public const MENSAJE_RESTRINGIDA = 'Tu cuenta está restringida: no podés solicitar adopciones ni publicar hasta que envíes la foto de seguimiento de tu adopción.';

    // Las fechas las calcula el sistema y la foto y el estado los cambian los controladores
    protected $fillable = ['ID_Adopcion', 'Fecha_Aviso', 'Fecha_Limite'];

    public function adopcion()
    {
        return $this->belongsTo(Adopcion::class, 'ID_Adopcion');
    }

    // ¿Ya puede enviar la foto? Desde el día del aviso (día 15)
    public function puedeEnviarFoto()
    {
        return $this->Fecha_Aviso <= now()->toDateString()
            && in_array($this->Estado_Evidencia, ['Pendiente', 'Rechazada']);
    }

    // ¿Este seguimiento restringe la cuenta del adoptante? Desde el día 22, si no hay una foto
    // presentada: nunca la envió (Pendiente) o el publicante la rechazó (Rechazada) (RNE21, D71).
    // Una foto Enviada cuenta como presentada aunque nadie la revise (D72).
    public function restringe()
    {
        return $this->Fecha_Limite <= now()->toDateString()
            && in_array($this->Estado_Evidencia, ['Pendiente', 'Rechazada']);
    }
}
