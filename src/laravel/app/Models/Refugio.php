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

    // Un refugio tiene uno o más datos de contacto
    public function contactos()
    {
        return $this->hasMany(RefugioDatoContacto::class, 'ID_Usuario');
    }

    // Cuentas para donaciones, en el orden en que se cargaron (RF:0035 a RF:0037)
    public function cuentas()
    {
        return $this->hasMany(CuentaBancaria::class, 'ID_Usuario')->orderBy('ID_Cuenta_Banco');
    }

    // ¿Ya marcó su ubicación en el mapa? Sin latitud y longitud no aparece en el mapa
    public function tieneUbicacion()
    {
        return $this->Latitud !== null && $this->Longitud !== null;
    }
}
