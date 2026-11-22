<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Seguimiento posterior a la adopción (tabla Seguimiento) (RF:0055, RF:0056, RF:0058)
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

    // ¿Este seguimiento restringe la cuenta del adoptante? Desde el día 22, si no hay una foto presentada
    public function restringe()
    {
        return $this->Fecha_Limite <= now()->toDateString()
            && in_array($this->Estado_Evidencia, ['Pendiente', 'Rechazada']);
    }
}
