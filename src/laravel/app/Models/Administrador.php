<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Administrador de la plataforma (tabla Administrador, 15.2.1).
 */
class Administrador extends Model
{
    use HasFactory;

    protected $table = 'Administrador';
    protected $primaryKey = 'ID_Usuario';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['ID_Usuario'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario');
    }
}
