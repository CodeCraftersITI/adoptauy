<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Cuenta de cualquier usuario registrado (tabla Usuario del DER, 15.2.1).
 * Tipo_Usuario vale 'Particular', 'Refugio' o 'Administrador', y cada usuario
 * tiene su fila en la tabla de ese tipo (RNE10, RNE11).
 */
class Usuario extends Model
{
    use HasFactory;   // permite crear usuarios de prueba con Usuario::factory()

    // La tabla y la clave no siguen la convención de Laravel (usuarios, id): se indican a mano
    protected $table = 'Usuario';
    protected $primaryKey = 'ID_Usuario';

    // El DER usa Fecha_Alta en lugar de created_at y updated_at
    public $timestamps = false;

    // Tipo_Usuario NO está en $fillable: así nadie puede mandar tipo=Administrador
    // desde un formulario y volverse administrador (mass assignment, como en el teórico)
    protected $fillable = ['Email', 'Contraseña'];

    // Relaciones 1 a 1 con cada subtipo (la clave de las dos tablas es ID_Usuario)
    public function particular()
    {
        return $this->hasOne(Particular::class, 'ID_Usuario');
    }

    public function refugio()
    {
        return $this->hasOne(Refugio::class, 'ID_Usuario');
    }

    public function administrador()
    {
        return $this->hasOne(Administrador::class, 'ID_Usuario');
    }

    // Nombre que se muestra en el encabezado y en los paneles
    public function nombreVisible()
    {
        if ($this->Tipo_Usuario == 'Particular')
        {
            return $this->particular->Nombre . ' ' . $this->particular->Apellido;
        }

        if ($this->Tipo_Usuario == 'Refugio')
        {
            return $this->refugio->Nombre_Refugio;
        }

        return 'Administrador';
    }
}
