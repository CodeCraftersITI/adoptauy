<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Solicitud de adopción de un Particular sobre una mascota En adopción (tabla Solicitud_Adopcion).
 * RF:0049. Estado_Solicitud: 'Pendiente', 'Adoptada' o 'Rechazada' (13.2.3).
 */
class SolicitudAdopcion extends Model
{
    protected $table = 'Solicitud_Adopcion';
    protected $primaryKey = 'ID_Solicitud';
    public $timestamps = false;

    // ID_Usuario y Estado_Solicitud no están: el solicitante es quien inició sesión y el
    // estado arranca en Pendiente (valor por defecto de la tabla)
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
}
