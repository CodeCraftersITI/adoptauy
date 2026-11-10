<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Publicación de un animal (tabla Publicacion_Animal): lo común a los dos tipos.
 * Tipo_Publicacion vale 'Adopción' o 'Perdida', y los datos propios de cada tipo
 * están en Publicacion_Adopcion o en Publicacion_Perdida (especialización).
 */
class PublicacionAnimal extends Model
{
    protected $table = 'Publicacion_Animal';
    protected $primaryKey = 'ID_Publicacion';
    public $timestamps = false;

    // ID_Usuario y Tipo_Publicacion no están: los asigna el controlador, nunca el formulario
    protected $fillable = ['Especie', 'Raza', 'Sexo'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario');
    }

    public function adopcion()
    {
        return $this->hasOne(PublicacionAdopcion::class, 'ID_Publicacion');
    }

    public function perdida()
    {
        return $this->hasOne(PublicacionPerdida::class, 'ID_Publicacion');
    }

    // Tipo para mostrar: Adopción, Perdida o Encontrada (las dos últimas comparten tabla)
    public function tipoVisible()
    {
        if ($this->Tipo_Publicacion == 'Adopción')
        {
            return 'Adopción';
        }

        return $this->perdida->Tipo_Aviso;
    }

    public function fotos()
    {
        return $this->hasMany(FotoAnimal::class, 'ID_Publicacion');
    }

    // Estado según el tipo: En adopción / Adoptado / Archivado o Vigente / Resuelta / Archivada
    public function estado()
    {
        if ($this->Tipo_Publicacion == 'Adopción')
        {
            return $this->adopcion->Estado_Adopcion;
        }

        return $this->perdida->Estado_Perdida;
    }

    // Ruta de la primera foto, para las tarjetas de los listados
    public function primeraFoto()
    {
        return '/storage/' . $this->fotos->first()->URL_Foto;
    }

    // ¿La puede ver cualquier visitante? Mismas condiciones que los listados del inicio:
    // visible por moderación (13.2.5) y en estado público (En adopción, o Vigente sin vencer)
    public function esPublica()
    {
        if ($this->Estado_Moderacion != 'Visible')
        {
            return false;
        }

        if ($this->Tipo_Publicacion == 'Adopción')
        {
            return $this->adopcion->Estado_Adopcion == 'En adopción';
        }

        return $this->perdida->Estado_Perdida == 'Vigente'
            && $this->perdida->Fecha_Vencimiento >= now()->toDateString();
    }

    // Quién publica, sin datos personales del particular (RNF:0013): de un refugio se
    // muestra su nombre; de un particular, solo que es un particular
    public function publicanteVisible()
    {
        if ($this->usuario->Tipo_Usuario == 'Refugio')
        {
            return 'Refugio ' . $this->usuario->refugio->Nombre_Refugio;
        }

        return 'Un particular';
    }
}
