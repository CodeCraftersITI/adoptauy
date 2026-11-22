<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Particular;
use App\Models\Refugio;
use App\Models\RefugioDatoContacto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// El Administrador modifica un refugio (RF:0007) o un particular (RF:0013), con las mismas reglas que el perfil y el registro
class AdminEditarTest extends TestCase
{
    use RefreshDatabase;

    private $sesionAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Administrador::factory()->create();
        $this->sesionAdmin = ['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'];
    }

    private function datosParticular($cambios = [])
    {
        return array_merge(['nombre' => 'Valentina', 'apellido' => 'Suárez', 'telefono' => '099111222', 'ci' => ''], $cambios);
    }

    // ---------- Particular (RF:0013) ----------

    public function test_rf0013_modifica_nombre_apellido_y_telefono()
    {
        $particular = Particular::factory()->create();
        $ci = $particular->CI;

        $this->withSession($this->sesionAdmin)->put('/admin/particulares/' . $particular->ID_Usuario, $this->datosParticular())
            ->assertRedirect('/admin/particulares')
            ->assertSessionHas('exito');

        $particular->refresh();
        $this->assertEquals('Valentina', $particular->Nombre);
        $this->assertEquals('099111222', $particular->Telefono);
        $this->assertEquals($ci, $particular->CI);   // vacía: no cambia
    }

    public function test_rnf0013_el_formulario_no_muestra_la_cedula()
    {
        $particular = Particular::factory()->create();

        $this->withSession($this->sesionAdmin)->get('/admin/particulares/' . $particular->ID_Usuario . '/editar')
            ->assertOk()
            ->assertSee($particular->Nombre)
            ->assertDontSee($particular->CI);
    }

    public function test_rf0013_corrige_la_cedula_si_es_valida_y_unica()
    {
        $particular = Particular::factory()->create();
        $otro = Particular::factory()->create();
        $url = '/admin/particulares/' . $particular->ID_Usuario;

        // Con dígito verificador incorrecto o repetida, se rechaza
        $this->withSession($this->sesionAdmin)->put($url, $this->datosParticular(['ci' => '12345678']))->assertSessionHasErrors('ci');
        $this->withSession($this->sesionAdmin)->put($url, $this->datosParticular(['ci' => $otro->CI]))->assertSessionHasErrors('ci');

        // Válida (1.234.567-2) y libre: se guarda
        $this->withSession($this->sesionAdmin)->put($url, $this->datosParticular(['ci' => '1.234.567-2']))->assertSessionHasNoErrors();
        $this->assertEquals('12345672', $particular->fresh()->CI);
    }

    public function test_rf0013_mismas_reglas_que_el_registro()
    {
        $particular = Particular::factory()->create();

        $this->withSession($this->sesionAdmin)->put('/admin/particulares/' . $particular->ID_Usuario, $this->datosParticular([
            'nombre' => 'A',
            'telefono' => '123',
        ]))->assertSessionHasErrors(['nombre', 'telefono']);
    }

    // ---------- Refugio (RF:0007) ----------

    public function test_rf0007_modifica_nombre_contactos_y_ubicacion()
    {
        $refugio = Refugio::factory()->validado()->create();
        RefugioDatoContacto::create(['ID_Usuario' => $refugio->ID_Usuario, 'Dato_Contacto' => '099000000']);

        $this->withSession($this->sesionAdmin)->get('/admin/refugios/' . $refugio->ID_Usuario . '/editar')
            ->assertOk()
            ->assertSee('099000000')
            ->assertSee('Suspender');   // la transición válida desde Validado

        $this->withSession($this->sesionAdmin)->put('/admin/refugios/' . $refugio->ID_Usuario, [
            'nombre_refugio' => 'Refugio de prueba Corregido',
            'ubicacion' => 'Cordón, Montevideo',
            'contactos' => "098555444\nrefugio@prueba.uy",
        ])->assertRedirect('/admin/refugios');

        $refugio->refresh();
        $this->assertEquals('Refugio de prueba Corregido', $refugio->Nombre_Refugio);
        $this->assertEquals('Cordón, Montevideo', $refugio->Ubicacion);
        $this->assertEqualsCanonicalizing(['098555444', 'refugio@prueba.uy'], $refugio->contactos()->pluck('Dato_Contacto')->all());
        // Editar no cambia el estado
        $this->assertEquals('Validado', $refugio->Estado_Validacion);
    }

    public function test_rf0007_el_refugio_necesita_nombre_y_al_menos_un_contacto()
    {
        $refugio = Refugio::factory()->validado()->create();

        $this->withSession($this->sesionAdmin)->put('/admin/refugios/' . $refugio->ID_Usuario, [
            'nombre_refugio' => '',
            'ubicacion' => 'Montevideo',
            'contactos' => '',
        ])->assertSessionHasErrors(['nombre_refugio', 'lista_contactos']);
    }

    // ---------- Acceso ----------

    public function test_solo_el_administrador_edita()
    {
        $particular = Particular::factory()->create();
        $refugio = Refugio::factory()->validado()->create();
        $sesionParticular = ['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'];

        $this->get('/admin/particulares/' . $particular->ID_Usuario . '/editar')->assertRedirect('/ingresar');
        $this->withSession($sesionParticular)->get('/admin/refugios/' . $refugio->ID_Usuario . '/editar')->assertForbidden();
        $this->withSession($sesionParticular)->put('/admin/particulares/' . $particular->ID_Usuario, $this->datosParticular())->assertForbidden();

        $this->assertNotEquals('Valentina', $particular->fresh()->Nombre);
    }
}
