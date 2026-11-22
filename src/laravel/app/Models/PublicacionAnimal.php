<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Publicación de un animal (tabla Publicacion_Animal)
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

    // ¿Se puede editar? Adoptado y Resuelta son estados finales (13.2.2): ya no se editan
    public function sePuedeEditar()
    {
        return !in_array($this->estado(), ['Adoptado', 'Resuelta']);
    }

    // Reportes recibidos por esta publicación (RF:0059)
    public function reportes()
    {
        return $this->hasMany(Reporte::class, 'ID_Publicacion_Reportada');
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

    // ¿La puede ver cualquier visitante? Mismas condiciones que los listados del inicio
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

    // Quién publica, sin datos personales del particular
    public function publicanteVisible()
    {
        // Se busca el refugio directamente
        $refugio = Refugio::find($this->ID_Usuario);
        if ($refugio)
        {
            return 'Refugio ' . $refugio->Nombre_Refugio;
        }

        return 'Un particular';
    }
}
