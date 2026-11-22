<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

// Reporte de una publicación, de un particular o de un refugio (tabla Reporte) (RF:0059 a RF:0061)
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

    // Umbrales de los reportes (RF:0061)
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

    // Medida automática al registrarse el reporte que alcanza un umbral (RF:0061)
    public function aplicarUmbrales()
    {
        $anterior = DB::getDefaultConnection();
        if (config('database.conexiones_por_rol'))
        {
            DB::setDefaultConnection('sistema');
        }

        $this->aplicarUmbralesComoSistema();

        DB::setDefaultConnection($anterior);
    }

    private function aplicarUmbralesComoSistema()
    {
        if ($this->ID_Publicacion_Reportada)
        {
            $publicacion = PublicacionAnimal::find($this->ID_Publicacion_Reportada);

            if ($publicacion->Estado_Moderacion == 'Visible'
                && Reporte::cantidadSobrePublicacion($publicacion->ID_Publicacion) >= self::UMBRAL_PUBLICACION)
            {
                $publicacion->Estado_Moderacion = 'En revisión';
                $publicacion->save();
            }

            return;
        }

        $usuario = Usuario::find($this->ID_Usuario_Reportado);

        if ($usuario->Tipo_Usuario == 'Refugio')
        {
            $refugio = Refugio::find($usuario->ID_Usuario);

            if ($refugio->Estado_Validacion == 'Validado'
                && Reporte::cantidadSobreUsuario($usuario->ID_Usuario) >= self::UMBRAL_REFUGIO)
            {
                $refugio->Estado_Validacion = 'Suspendido';
                $refugio->save();
            }

            return;
        }

        Reporte::revisarParticular($usuario);
    }

    // Revisión o inhabilitación automática del Particular (RF:0060)
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
