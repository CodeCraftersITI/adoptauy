<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Persona física registrada (tabla Particular, 15.2.1). RF:0039.
 * RNF:0013: la CI nunca se muestra a otros usuarios.
 */
class Particular extends Model
{
    use HasFactory;

    protected $table = 'Particular';
    protected $primaryKey = 'ID_Usuario';

    // La clave es la misma del Usuario, no se genera sola
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['ID_Usuario', 'CI', 'Nombre', 'Apellido', 'Telefono'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario');
    }
}
