<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Solicitud de adopción de un Particular sobre una mascota En adopción (tabla Solicitud_Adopcion) (RF:0049)
class SolicitudAdopcion extends Model
{
    protected $table = 'Solicitud_Adopcion';
    protected $primaryKey = 'ID_Solicitud';
    public $timestamps = false;

    // ID_Usuario y Estado_Solicitud no están en $fillable
    protected $fillable = ['ID_Publicacion'];

    // La mascota pedida (datos propios de la adopción)
    public function adopcion()
    {
        return $this->belongsTo(PublicacionAdopcion::class, 'ID_Publicacion');
    }

    // Quién la pidió
    public function particular()
    {
        return $this->belongsTo(Particular::class, 'ID_Usuario');
    }

    // La adopción que se concretó con esta solicitud, si fue la elegida (RF:0052)
    public function adopcionConcretada()
    {
        return $this->hasOne(Adopcion::class, 'ID_Solicitud');
    }
}
