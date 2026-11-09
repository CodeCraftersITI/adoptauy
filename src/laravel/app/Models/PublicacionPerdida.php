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

    // Contacto para mostrar: se guarda solo con números y se agrupa para leerlo mejor
    // (celular 098 765 432, fijo 2901 2345). Si no son 8 o 9 dígitos, se muestra tal cual.
    public function contactoFormateado()
    {
        $numero = $this->Contacto;
        if (preg_match('/^[0-9]{9}$/', $numero))
        {
            return substr($numero, 0, 3) . ' ' . substr($numero, 3, 3) . ' ' . substr($numero, 6);
        }
        if (preg_match('/^[0-9]{8}$/', $numero))
        {
            return substr($numero, 0, 4) . ' ' . substr($numero, 4);
        }

        return $numero;
    }

    public function publicacion()
    {
        return $this->belongsTo(PublicacionAnimal::class, 'ID_Publicacion');
    }
}
