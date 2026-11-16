<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Reporte de una publicación, de un particular o de un refugio (tabla Reporte, 15.2.1).
 * RF:0059 a RF:0061. Se completa ID_Usuario_Reportado o ID_Publicacion_Reportada, nunca los dos (RNE22).
 * Estado_Reporte: 'Pendiente', 'Desestimado' o 'Confirmado'. Medida: 'Aviso', 'Suspensión' o 'Baja lógica'.
 */
class Reporte extends Model
{
    protected $table = 'Reporte';
    protected $primaryKey = 'ID_Reporte';
    public $timestamps = false;

    // El reportante sale de la sesión y el estado, la medida y la fecha los pone el Administrador
    protected $fillable = ['ID_Usuario_Reportado', 'ID_Publicacion_Reportada', 'Motivo', 'Detalle'];

    // Motivos de 13.2.5, uno por tipo de objeto reportado. "Otro" pide el texto en Detalle
    public const MOTIVOS = [
        'Publicación' => [
            'Publicación falsa o engañosa',
            'Publicación duplicada o spam',
            'Animal ya adoptado o no disponible',
            'Fotos o contenido inapropiado',
            'Venta de animales',
            'Sospecha de maltrato o criadero ilegal',
            'Datos de contacto falsos o incorrectos',
            'Otro',
        ],
        'Particular' => [
            'Usuario malicioso o fraudulento',
            'Suplantación de identidad',
            'Acoso o mensajes abusivos',
            'Cuenta falsa o duplicada',
            'Otro',
        ],
        'Refugio' => [
            'Refugio no legítimo (documentación falsa)',
            'La cuenta bancaria publicada no pertenece al refugio',
            'Datos de ubicación o contacto falsos',
            'Maltrato animal o condiciones inadecuadas',
            'Suplantación de otro refugio',
            'Otro',
        ],
    ];

    // Umbrales de 13.2.5 (RF:0061). Solo cuentan los reportes no desestimados; como un usuario
    // no puede reportar dos veces lo mismo (UNIQUE), cada reporte es de un usuario distinto
    public const UMBRAL_PUBLICACION = 3;        // publicación: pasa a En revisión y se oculta
    public const UMBRAL_REFUGIO = 3;            // refugio: pasa a Suspendido
    public const UMBRAL_REVISION = 5;           // particular: revisión de cuenta
    public const UMBRAL_SUSPENDIDAS = 3;        // particular: revisión por publicaciones suspendidas
    public const UMBRAL_INHABILITAR = 10;       // particular: cuenta inhabilitada

    // Cuántos reportes que cuentan tiene una publicación o un usuario
    public static function cantidadSobrePublicacion($idPublicacion)
    {
        return Reporte::where('ID_Publicacion_Reportada', $idPublicacion)
            ->where('Estado_Reporte', '!=', 'Desestimado')
            ->count();
    }

    public static function cantidadSobreUsuario($idUsuario)
    {
        return Reporte::where('ID_Usuario_Reportado', $idUsuario)
            ->where('Estado_Reporte', '!=', 'Desestimado')
            ->count();
    }

    // Medida automática al registrarse el reporte que alcanza un umbral (RF:0061).
    // Solo se aplica sobre el estado normal; si el Administrador ya tomó otra medida, no se pisa
    public function aplicarUmbrales()
    {
        if ($this->ID_Publicacion_Reportada)
        {
            $publicacion = $this->publicacion;

            if ($publicacion->Estado_Moderacion == 'Visible'
                && Reporte::cantidadSobrePublicacion($publicacion->ID_Publicacion) >= self::UMBRAL_PUBLICACION)
            {
                $publicacion->Estado_Moderacion = 'En revisión';
                $publicacion->save();
            }

            return;
        }

        $usuario = $this->reportado;

        if ($usuario->Tipo_Usuario == 'Refugio')
        {
            if ($usuario->refugio->Estado_Validacion == 'Validado'
                && Reporte::cantidadSobreUsuario($usuario->ID_Usuario) >= self::UMBRAL_REFUGIO)
            {
                $usuario->refugio->Estado_Validacion = 'Suspendido';
                $usuario->refugio->save();
            }

            return;
        }

        Reporte::revisarParticular($usuario);
    }

    // Particular: 10 reportes -> Inhabilitado; 5 reportes o 3 publicaciones suspendidas -> En revisión.
    // También se llama cuando el Administrador suspende una publicación (RF:0060)
    public static function revisarParticular($usuario)
    {
        if ($usuario->Tipo_Usuario != 'Particular' || !in_array($usuario->Estado_Usuario, ['Activo', 'En revisión']))
        {
            return;
        }

        $reportes = Reporte::cantidadSobreUsuario($usuario->ID_Usuario);
        $suspendidas = $usuario->publicaciones()->where('Estado_Moderacion', 'Suspendida')->count();

        if ($reportes >= self::UMBRAL_INHABILITAR)
        {
            $usuario->Estado_Usuario = 'Inhabilitado';
        }
        elseif ($reportes >= self::UMBRAL_REVISION || $suspendidas >= self::UMBRAL_SUSPENDIDAS)
        {
            $usuario->Estado_Usuario = 'En revisión';
        }

        $usuario->save();
    }

    public function reportante()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario_Reportante');
    }

    public function reportado()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario_Reportado');
    }

    public function publicacion()
    {
        return $this->belongsTo(PublicacionAnimal::class, 'ID_Publicacion_Reportada');
    }
}
