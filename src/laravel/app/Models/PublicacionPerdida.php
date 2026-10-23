<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Datos propios de una publicación de mascota perdida (tabla Publicacion_Perdida). RF:0046.
 * Estado_Perdida: 'Perdida', 'Encontrada' o 'Archivada' (13.2.2).
 * Vence 30 días después de la creación o de la última renovación (RNE17).
 */
class PublicacionPerdida extends Model
{
    protected $table = 'Publicacion_Perdida';
    protected $primaryKey = 'ID_Publicacion';
    public $incrementing = false;
    public $timestamps = false;

    // Estado_Perdida y Fecha_Vencimiento no están: los fija el sistema, no el formulario
    protected $fillable = [
        'ID_Publicacion', 'Nombre_Mascota', 'Caracteristicas',
        'Fecha_Extravio', 'Zona_Extravio', 'Contacto',
    ];

    public function publicacion()
    {
        return $this->belongsTo(PublicacionAnimal::class, 'ID_Publicacion');
    }
}
