<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Un dato de contacto de un refugio (tabla Refugio_Datos_Contacto, 15.2.1).
 * La clave de la tabla es ID_Usuario + Dato_Contacto; acá solo se crean y se leen.
 */
class RefugioDatoContacto extends Model
{
    protected $table = 'Refugio_Datos_Contacto';
    protected $primaryKey = 'ID_Usuario';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['ID_Usuario', 'Dato_Contacto'];
}
