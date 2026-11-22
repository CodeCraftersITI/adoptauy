<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Adopción concretada (tabla Adopcion) (RF:0052)
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

    // Su seguimiento: uno por adopción (RNE20)
    public function seguimiento()
    {
        return $this->hasOne(Seguimiento::class, 'ID_Adopcion');
    }
}
