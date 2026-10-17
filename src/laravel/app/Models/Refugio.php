<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Refugio u organización (tabla Refugio, 15.2.1). RF:0034.
 * Estado_Validacion: 'Pendiente de validación', 'Validado', 'Rechazado',
 * 'Suspendido' o 'Dado de baja' (13.2.1, RNE24).
 */
class Refugio extends Model
{
    use HasFactory;

    protected $table = 'Refugio';
    protected $primaryKey = 'ID_Usuario';
    public $incrementing = false;
    public $timestamps = false;

    // Estado_Validacion no está: el estado lo cambia solo el Administrador
    protected $fillable = [
        'ID_Usuario', 'Nombre_Refugio', 'Certificado_Personeria', 'Comprobante_Domicilio',
        'Ubicacion', 'Latitud', 'Longitud', 'URL_Foto',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario');
    }
}
