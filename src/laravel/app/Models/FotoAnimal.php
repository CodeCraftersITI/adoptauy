<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Una fotografía de una publicación (tabla Foto_Animal). RNE01.
 * URL_Foto es la ruta dentro de storage/app/public; en el navegador se ve en /storage/...
 */
class FotoAnimal extends Model
{
    protected $table = 'Foto_Animal';
    protected $primaryKey = 'ID_Foto';
    public $timestamps = false;

    protected $fillable = ['ID_Publicacion', 'URL_Foto'];

    // La publicación a la que pertenece la foto
    public function publicacion()
    {
        return $this->belongsTo(PublicacionAnimal::class, 'ID_Publicacion');
    }
}
