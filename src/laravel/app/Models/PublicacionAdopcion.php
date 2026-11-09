<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Datos propios de una publicación en adopción (tabla Publicacion_Adopcion). RF:0017, RF:0024.
 * Estado_Adopcion: 'En adopción', 'Adoptado' o 'Archivado' (13.2.2).
 */
class PublicacionAdopcion extends Model
{
    protected $table = 'Publicacion_Adopcion';
    protected $primaryKey = 'ID_Publicacion';
    public $incrementing = false;
    public $timestamps = false;

    // Estado_Adopcion no está: arranca en 'En adopción' (valor por defecto de la tabla)
    protected $fillable = [
        'ID_Publicacion', 'Edad_Meses', 'Tamaño', 'Estado_Salud',
        'Estado_Esterilizacion', 'Ubicacion_Aproximada',
    ];

    public function publicacion()
    {
        return $this->belongsTo(PublicacionAnimal::class, 'ID_Publicacion');
    }

    // Edad para mostrar: "8 meses", "1 año", "2 años y 3 meses"
    public function edadTexto()
    {
        $anios = intdiv($this->Edad_Meses, 12);
        $meses = $this->Edad_Meses % 12;

        if ($anios == 0)
        {
            return $meses == 1 ? '1 mes' : $meses . ' meses';
        }

        $texto = $anios == 1 ? '1 año' : $anios . ' años';

        if ($meses > 0)
        {
            $texto = $texto . ' y ' . ($meses == 1 ? '1 mes' : $meses . ' meses');
        }

        return $texto;
    }
}
