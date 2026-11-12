<?php

namespace Tests\Feature;

use App\Models\CuentaBancaria;
use App\Models\Particular;
use App\Models\Refugio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sección de refugios: mapa y listado (RF:0038, RF:0042), perfil público (RF:0062)
 * y cuentas bancarias para donaciones (RF:0035 a RF:0037). Casos CP-20 y CP-21.
 */
class RefugioTest extends TestCase
{
    use RefreshDatabase;

    private function sesion($refugio)
    {
        return ['id_usuario' => $refugio->ID_Usuario, 'tipo' => 'Refugio', 'nombre' => $refugio->Nombre_Refugio];
    }

    private function crearCuenta($refugio, $numero = 1)
    {
        $cuenta = new CuentaBancaria([
            'Banco' => 'Banco de prueba',
            'Tipo_Cuenta' => 'Caja de ahorro',
            'Numero_Cuenta' => '000111222',
            'Titular' => 'Titular de prueba',
        ]);
        $cuenta->ID_Usuario = $refugio->ID_Usuario;
        $cuenta->ID_Cuenta_Banco = $numero;
        $cuenta->save();

        return $cuenta;
    }

    // ---------- CP-20: mapa, listado y perfil público ----------

    public function test_cp20_el_listado_muestra_solo_los_refugios_validados()
    {
        Refugio::factory()->validado()->create(['Nombre_Refugio' => 'Refugio validado de prueba']);
        Refugio::factory()->create(['Nombre_Refugio' => 'Refugio pendiente de prueba']);
        Refugio::factory()->validado()->create(['Nombre_Refugio' => 'Refugio suspendido de prueba', 'Estado_Validacion' => 'Suspendido']);

        $this->get('/refugios')
            ->assertOk()
            ->assertSee('Refugio validado de prueba')
            ->assertDontSee('Refugio pendiente de prueba')
            ->assertDontSee('Refugio suspendido de prueba');
    }

    public function test_cp20_el_mapa_recibe_solo_los_validados_con_ubicacion()
    {
        $conUbicacion = Refugio::factory()->validado()->create(['Latitud' => -34.9, 'Longitud' => -56.16]);
        Refugio::factory()->validado()->create();   // sin ubicación: solo en el listado
        Refugio::factory()->create(['Latitud' => -34.88, 'Longitud' => -56.18]);   // pendiente: en ningún lado

        $marcadores = $this->get('/refugios')->viewData('marcadores');

        $this->assertCount(1, $marcadores);
        $this->assertEquals('/refugios/' . $conUbicacion->ID_Usuario, $marcadores[0]['perfil']);
    }

    public function test_cp20_el_perfil_muestra_los_datos_y_las_cuentas()
    {
        $refugio = Refugio::factory()->validado()->create(['Nombre_Refugio' => 'Refugio perfil de prueba']);
        $this->crearCuenta($refugio);

        $this->get('/refugios/' . $refugio->ID_Usuario)
            ->assertOk()
            ->assertSee('Refugio perfil de prueba')
            ->assertSee('Banco de prueba')
            ->assertSee('000111222')
            ->assertSee('Titular de prueba');
    }

    public function test_cp20_el_perfil_de_un_refugio_no_validado_no_existe()
    {
        $pendiente = Refugio::factory()->create();
        $suspendido = Refugio::factory()->validado()->create(['Estado_Validacion' => 'Suspendido']);

        $this->get('/refugios/' . $pendiente->ID_Usuario)->assertNotFound();
        $this->get('/refugios/' . $suspendido->ID_Usuario)->assertNotFound();
        $this->get('/refugios/99999')->assertNotFound();
    }

    // ---------- CP-21: cuentas bancarias ----------

    public function test_cp21_el_refugio_agrega_una_cuenta_completa()
    {
        $refugio = Refugio::factory()->validado()->create();

        $this->withSession($this->sesion($refugio))->post('/mi-refugio/cuentas', [
            'banco' => 'Banco de prueba',
            'tipo_cuenta' => 'Caja de ahorro',
            'numero_cuenta' => '123456',
            'titular' => 'Titular de prueba',
        ])->assertRedirect('/mi-refugio');

        $this->assertEquals(1, $refugio->cuentas()->count());
        $this->get('/refugios/' . $refugio->ID_Usuario)->assertSee('123456');
    }

    public function test_cp21_no_se_acepta_una_cuenta_sin_numero_o_sin_titular()
    {
        $refugio = Refugio::factory()->validado()->create();

        $this->withSession($this->sesion($refugio))->post('/mi-refugio/cuentas', [
            'banco' => 'Banco de prueba',
            'tipo_cuenta' => 'Caja de ahorro',
            'numero_cuenta' => '',
            'titular' => '',
        ])->assertSessionHasErrors(['numero_cuenta', 'titular']);

        $this->assertEquals(0, $refugio->cuentas()->count());
    }

    public function test_cp21_al_quitar_la_cuenta_sale_del_perfil()
    {
        $refugio = Refugio::factory()->validado()->create();
        $this->crearCuenta($refugio);

        $this->withSession($this->sesion($refugio))->delete('/mi-refugio/cuentas/1')->assertRedirect('/mi-refugio');

        $this->get('/refugios/' . $refugio->ID_Usuario)->assertDontSee('000111222');
    }

    public function test_cp21_un_refugio_no_puede_quitar_la_cuenta_de_otro()
    {
        $duenio = Refugio::factory()->validado()->create();
        $otro = Refugio::factory()->validado()->create();
        $this->crearCuenta($duenio);

        $this->withSession($this->sesion($otro))->delete('/mi-refugio/cuentas/1')->assertNotFound();

        $this->assertEquals(1, $duenio->cuentas()->count());
    }

    // Acceso por rol a /mi-refugio: el Invitado va a ingresar; Particular y Administrador, 403
    public function test_cp21_solo_el_refugio_entra_a_mi_refugio()
    {
        $particular = Particular::factory()->create();
        $refugio = Refugio::factory()->validado()->create();

        $this->get('/mi-refugio')->assertRedirect('/ingresar');

        $this->withSession(['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'])
            ->get('/mi-refugio')
            ->assertForbidden();

        $this->withSession(['id_usuario' => 1, 'tipo' => 'Administrador', 'nombre' => 'Administrador'])
            ->get('/mi-refugio')
            ->assertForbidden();

        $this->withSession($this->sesion($refugio))->get('/mi-refugio')->assertOk();
    }

    // ---------- Ubicación en el mapa ----------

    public function test_el_refugio_guarda_su_ubicacion_en_montevideo()
    {
        $refugio = Refugio::factory()->validado()->create();

        $this->withSession($this->sesion($refugio))
            ->put('/mi-refugio/ubicacion', ['latitud' => -34.905, 'longitud' => -56.19])
            ->assertRedirect('/mi-refugio');

        $this->assertTrue($refugio->fresh()->tieneUbicacion());
    }

    public function test_no_se_acepta_una_ubicacion_fuera_de_montevideo()
    {
        $refugio = Refugio::factory()->validado()->create();

        // Buenos Aires
        $this->withSession($this->sesion($refugio))
            ->put('/mi-refugio/ubicacion', ['latitud' => -34.6, 'longitud' => -58.38])
            ->assertSessionHasErrors('longitud');

        $this->assertFalse($refugio->fresh()->tieneUbicacion());
    }
}
