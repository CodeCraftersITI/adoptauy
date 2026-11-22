<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Cuenta de cualquier usuario registrado (tabla Usuario)
class Usuario extends Model
{
    use HasFactory;   // permite crear usuarios de prueba con Usuario::factory()
    use ColumnasPermitidas;

    // Columnas que puede leer cada usuario de MySQL (16.3.4); admin lee todas
    public const COLUMNAS_POR_CONEXION = [
        'login' => ['ID_Usuario', 'Email', 'Contraseña', 'Tipo_Usuario', 'Estado_Usuario', 'Email_Verificado', 'Google_ID'],
        'publico' => ['ID_Usuario', 'Email', 'Google_ID'],
        'particular' => ['ID_Usuario', 'Email', 'Contraseña', 'Tipo_Usuario', 'Estado_Usuario', 'Fecha_Alta', 'Google_ID'],
        'refugio' => ['ID_Usuario', 'Email', 'Contraseña', 'Tipo_Usuario', 'Estado_Usuario', 'Fecha_Alta'],
        'sistema' => ['ID_Usuario', 'Email', 'Tipo_Usuario', 'Estado_Usuario'],
    ];

    // La tabla y la clave no siguen la convención de Laravel (usuarios, id): se indican a mano
    protected $table = 'Usuario';
    protected $primaryKey = 'ID_Usuario';

    // El DER usa Fecha_Alta en lugar de created_at y updated_at
    public $timestamps = false;

    // Tipo_Usuario no está en $fillable
    protected $fillable = ['Email', 'Contraseña'];

    // Contraseña (RF:0067)
    public const REGLA_CONTRASENA = 'required|min:8|max:72';

    public const MENSAJES_CONTRASENA = [
        'password.required' => 'Ingresá una contraseña.',
        'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        'password.max' => 'La contraseña puede tener hasta 72 caracteres.',
    ];

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

    // Consulta con adoptauy_login
    public static function paraLogin()
    {
        return config('database.conexiones_por_rol') ? static::on('login') : static::query();
    }

    // Reportes recibidos por este particular o refugio (RF:0059)
    public function reportesRecibidos()
    {
        return $this->hasMany(Reporte::class, 'ID_Usuario_Reportado');
    }

    // Sus publicaciones
    public function publicaciones()
    {
        return $this->hasMany(PublicacionAnimal::class, 'ID_Usuario');
    }

    // Cuenta restringida desde el día 22 de una adopción sin foto presentada (RF:0058)
    public function tieneCuentaRestringida()
    {
        return Seguimiento::where('Fecha_Limite', '<=', now()->toDateString())
            ->whereIn('Estado_Evidencia', ['Pendiente', 'Rechazada'])
            ->whereHas('adopcion.solicitud', function ($solicitud)
            {
                $solicitud->where('ID_Usuario', $this->ID_Usuario);
            })
            ->exists();
    }

    // Lo mismo, a partir del ID de la sesión. Si el usuario no existe, no está restringido
    public static function estaRestringido($idUsuario)
    {
        $usuario = Usuario::find($idUsuario);

        return $usuario && $usuario->tieneCuentaRestringida();
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
