<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Persona física registrada (tabla Particular) (RF:0039)
class Particular extends Model
{
    use HasFactory;
    use ColumnasPermitidas;

    // Columnas que puede leer cada usuario de MySQL
    public const COLUMNAS_POR_CONEXION = [
        'publico' => ['ID_Usuario', 'CI'],
        'particular' => ['ID_Usuario', 'Nombre', 'Apellido', 'Telefono'],
        'refugio' => ['ID_Usuario', 'Nombre', 'Apellido', 'Telefono'],
        'sistema' => ['ID_Usuario', 'Nombre', 'Apellido'],
    ];

    protected $table = 'Particular';
    protected $primaryKey = 'ID_Usuario';

    // La clave es la misma del Usuario, no se genera sola
    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['ID_Usuario', 'CI', 'Nombre', 'Apellido', 'Telefono'];

    // Reglas y mensajes de nombre, apellido y teléfono (RF:0039, RF:0066, RF:0016)
    public const REGLAS = [
        'nombre' => 'required|min:2|max:50',
        'apellido' => 'required|min:2|max:50',
        'telefono' => 'required|regex:/^[0-9]{8,9}$/',
    ];

    public const MENSAJES = [
        'nombre.required' => 'Ingresá tu nombre.',
        'nombre.min' => 'El nombre debe tener al menos 2 letras.',
        'nombre.max' => 'El nombre puede tener hasta 50 letras.',
        'apellido.required' => 'Ingresá tu apellido.',
        'apellido.min' => 'El apellido debe tener al menos 2 letras.',
        'apellido.max' => 'El apellido puede tener hasta 50 letras.',
        'telefono.required' => 'Ingresá tu teléfono.',
        'telefono.regex' => 'El teléfono debe tener 8 o 9 números.',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario');
    }
}
