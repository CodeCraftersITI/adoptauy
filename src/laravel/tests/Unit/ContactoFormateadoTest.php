<?php

namespace Tests\Unit;

use App\Models\PublicacionPerdida;
use Tests\TestCase;

/**
 * Formato del contacto de una mascota perdida en el listado público (RF:0048).
 * Se guarda solo con números (RF:0046) y se muestra agrupado para leerlo mejor.
 */
class ContactoFormateadoTest extends TestCase
{
    private function formatear($contacto)
    {
        return (new PublicacionPerdida(['Contacto' => $contacto]))->contactoFormateado();
    }

    public function test_celular_de_9_digitos_se_agrupa_de_a_3()
    {
        $this->assertSame('098 765 432', $this->formatear('098765432'));
    }

    public function test_fijo_de_8_digitos_se_agrupa_de_a_4()
    {
        $this->assertSame('2901 2345', $this->formatear('29012345'));
    }

    public function test_un_valor_anterior_a_la_validacion_se_muestra_tal_cual()
    {
        $this->assertSame('099 000 000', $this->formatear('099 000 000'));
    }
}
