<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Datos propios de una publicación de mascota perdida o encontrada (tabla Publicacion_Perdida).
 * RF:0046 (perdida) y RF:0064 (encontrada): comparten los datos, y Tipo_Aviso los distingue.
 * Fecha_Suceso y Zona_Suceso son las del extravío en una perdida y las del hallazgo en una encontrada.
 * Estado_Perdida: 'Vigente', 'Resuelta' o 'Archivada' (13.2.2).
 * Vence 30 días después de la creación o de la última renovación (RNE17).
 */
class PublicacionPerdida extends Model
{
    public const PERDIDA = 'Perdida';
    public const ENCONTRADA = 'Encontrada';

    protected $table = 'Publicacion_Perdida';
    protected $primaryKey = 'ID_Publicacion';
    public $incrementing = false;
    public $timestamps = false;

    // Estado_Perdida y Fecha_Vencimiento no están: los fija el sistema, no el formulario
    protected $fillable = [
        'ID_Publicacion', 'Tipo_Aviso', 'Nombre_Mascota', 'Caracteristicas',
        'Fecha_Suceso', 'Zona_Suceso', 'Contacto',
    ];

    // Publicaciones vigentes de un tipo para la consulta pública (RF:0048, RF:0065):
    // no vencidas (RF:0047) y no ocultas por moderación (13.2.5).
    // El día del vencimiento ya no se muestra: ese día la tarea de las 03:00 la archiva (CP-14).
    public function scopeVigentes($consulta, $tipo)
    {
        return $consulta->where('Tipo_Aviso', $tipo)
            ->where('Estado_Perdida', 'Vigente')
            ->where('Fecha_Vencimiento', '>', now()->toDateString())
            ->whereHas('publicacion', function ($publicacion)
            {
                $publicacion->where('Estado_Moderacion', 'Visible');
            });
    }

    public function esEncontrada()
    {
        return $this->Tipo_Aviso == self::ENCONTRADA;
    }

    // Nombre para mostrar: una mascota encontrada puede no tenerlo
    public function nombreVisible()
    {
        return $this->Nombre_Mascota ?: 'Sin nombre';
    }

    // Contacto para mostrar: se guarda solo con números y se agrupa para leerlo mejor
    // (celular 098 765 432, fijo 2901 2345). Si no son 8 o 9 dígitos, se muestra tal cual.
    public function contactoFormateado()
    {
        $numero = $this->Contacto;
        if (preg_match('/^[0-9]{9}$/', $numero))
        {
            return substr($numero, 0, 3) . ' ' . substr($numero, 3, 3) . ' ' . substr($numero, 6);
        }
        if (preg_match('/^[0-9]{8}$/', $numero))
        {
            return substr($numero, 0, 4) . ' ' . substr($numero, 4);
        }

        return $numero;
    }

    public function publicacion()
    {
        return $this->belongsTo(PublicacionAnimal::class, 'ID_Publicacion');
    }
}
