<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Adopción concretada (tabla Adopcion, 15.2.1): la genera la solicitud elegida (RF:0052).
 * Fecha_Adopcion es el día en que el publicante eligió al adoptante.
 */
class Adopcion extends Model
{
    protected $table = 'Adopcion';
    protected $primaryKey = 'ID_Adopcion';
    public $timestamps = false;

    protected $fillable = ['ID_Solicitud', 'Fecha_Adopcion'];

    // La solicitud elegida (de ahí salen la mascota y el adoptante)
    public function solicitud()
    {
        return $this->belongsTo(SolicitudAdopcion::class, 'ID_Solicitud');
    }
}
