<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla de validación para la cédula uruguaya (RF:0039, RNE23, D64).
 * Se usa en $request->validate() así: 'ci' => ['required', new CedulaUruguaya]
 *
 * La cédula tiene 7 u 8 dígitos y el último es el dígito verificador.
 * Ejemplo válido: 1.234.567-2
 */
class CedulaUruguaya implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!self::esValida($value))
        {
            $fail('La cédula no es válida. Revisá los números y el dígito verificador.');
        }
    }

    public static function esValida($ci)
    {
        // Quitamos puntos, guion y espacios
        $ci = str_replace(['.', '-', ' '], '', (string) $ci);

        // Tienen que ser 7 u 8 números
        if (!preg_match('/^[0-9]{7,8}$/', $ci))
        {
            return false;
        }

        $base = substr($ci, 0, -1);           // todos menos el último
        $verificador = (int) substr($ci, -1); // el último

        return self::digitoVerificador($base) == $verificador;
    }

    // Cálculo del dígito verificador: cada dígito se multiplica por 2, 9, 8, 7, 6, 3, 4
    public static function digitoVerificador($base)
    {
        $base = str_pad($base, 7, '0', STR_PAD_LEFT);   // 123456 pasa a 0123456
        $pesos = [2, 9, 8, 7, 6, 3, 4];
        $suma = 0;

        for ($i = 0; $i < 7; $i++)
        {
            $suma = $suma + $base[$i] * $pesos[$i];
        }

        return (10 - $suma % 10) % 10;
    }
}
