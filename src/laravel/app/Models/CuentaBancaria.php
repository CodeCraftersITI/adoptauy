<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cuenta bancaria de un refugio para donaciones por transferencia (tabla Cuenta_Bancaria).
 * RF:0035 a RF:0037. La plataforma no procesa pagos: solo muestra los datos (D4).
 *
 * La clave de la tabla es doble (ID_Usuario + ID_Cuenta_Banco) y Eloquent trabaja con una
 * sola: por eso las cuentas se borran con where() por las dos columnas (ver MiRefugioController).
 */
class CuentaBancaria extends Model
{
    protected $table = 'Cuenta_Bancaria';
    protected $primaryKey = 'ID_Cuenta_Banco';
    public $incrementing = false;
    public $timestamps = false;

    // ID_Usuario e ID_Cuenta_Banco no están: los pone el controlador, no el formulario
    protected $fillable = ['Banco', 'Tipo_Cuenta', 'Numero_Cuenta', 'Titular'];
}
