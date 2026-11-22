<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Cuenta bancaria de un refugio para donaciones por transferencia (tabla Cuenta_Bancaria) (RF:0035 a RF:0037)
class CuentaBancaria extends Model
{
    protected $table = 'Cuenta_Bancaria';
    protected $primaryKey = 'ID_Cuenta_Banco';
    public $incrementing = false;
    public $timestamps = false;

    // ID_Usuario e ID_Cuenta_Banco no están: los pone el controlador, no el formulario
    protected $fillable = ['Banco', 'Tipo_Cuenta', 'Numero_Cuenta', 'Titular'];
}
