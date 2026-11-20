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

    // Conexiones de privilegio mínimo para el inicio de sesión y el registro (config/database.php,
    // 16.3.4, RNF:0007): cada una entra a MySQL con un usuario que solo puede hacer eso
    public const CONEXION_LOGIN = 'mysql_login';        // adoptauy_login
    public const CONEXION_REGISTRO = 'mysql_publico';   // adoptauy_publico

    // Columnas de Usuario que adoptauy_login puede leer. Se piden por nombre porque un
    // SELECT * le está denegado: no ve Fecha_Alta ni ningún dato personal de otras tablas
    public const COLUMNAS_LOGIN = ['ID_Usuario', 'Email', 'Contraseña', 'Tipo_Usuario',
        'Estado_Usuario', 'Email_Verificado', 'Google_ID'];

    // Busca la cuenta para iniciar sesión con la conexión de adoptauy_login (RF:0022, RF:0066)
    public static function paraLogin(string $columna, string $valor): ?Usuario
    {
        return static::on(self::CONEXION_LOGIN)
            ->select(self::COLUMNAS_LOGIN)
            ->where($columna, $valor)
            ->first();
    }

    // Contraseña: entre 8 y 72 caracteres (13.2.1; 72 es el máximo que admite bcrypt, RNF:0007).
    // La usan el registro, el registro de refugio y el cambio de contraseña (RF:0067)
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

    // ¿Tiene la cuenta restringida por un seguimiento incumplido? (RF:0058, RNE21)
    // Desde el día 22 de una adopción suya sin foto presentada. Mientras tanto no puede
    // solicitar adopciones ni publicar; solo enviar la foto, y al enviarla se levanta sola.
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
