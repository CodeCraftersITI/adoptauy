<?php

namespace Tests\Unit;

use App\Rules\CedulaUruguaya;
use PHPUnit\Framework\TestCase;

/**
 * Dígito verificador de la cédula (RF:0039, RNE23, D64). Parte de CP-02 d).
 */
class CedulaUruguayaTest extends TestCase
{
    public function test_acepta_cedulas_validas()
    {
        $this->assertTrue(CedulaUruguaya::esValida('1.234.567-2'));   // con puntos y guion
        $this->assertTrue(CedulaUruguaya::esValida('12345672'));      // solo números
        $this->assertTrue(CedulaUruguaya::esValida('1234561'));       // de 7 dígitos
    }

    public function test_rechaza_cedulas_invalidas()
    {
        $this->assertFalse(CedulaUruguaya::esValida('1.234.567-3'));  // dígito verificador incorrecto
        $this->assertFalse(CedulaUruguaya::esValida('123456'));       // 6 dígitos
        $this->assertFalse(CedulaUruguaya::esValida('123456789'));    // 9 dígitos
        $this->assertFalse(CedulaUruguaya::esValida('1234567A'));     // con letras
        $this->assertFalse(CedulaUruguaya::esValida(''));             // vacía
    }
}
