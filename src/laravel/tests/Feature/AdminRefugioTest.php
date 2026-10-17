<?php

namespace Tests\Feature;

use App\Mail\RefugioDadoDeAlta;
use App\Models\Administrador;
use App\Models\Refugio;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Validación o rechazo de la solicitud de refugio por el Administrador
 * (RF:0005, RF:0044, RNE05). Caso CP-18.
 */
class AdminRefugioTest extends TestCase
{
    use RefreshDatabase;

    private $sesionAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $admin = Administrador::factory()->create();
        $this->sesionAdmin = ['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'];
    }

    public function test_la_solicitud_pendiente_aparece_en_el_panel()
    {
        $refugio = Refugio::factory()->create(['Nombre_Refugio' => 'Refugio pendiente de prueba']);

        $this->withSession($this->sesionAdmin)->get('/admin')
            ->assertOk()
            ->assertSee('Refugio pendiente de prueba');
    }

    public function test_cp18_validado_puede_iniciar_sesion_y_recibe_el_correo_de_alta()
    {
        $refugio = Refugio::factory()->create();

        $this->withSession($this->sesionAdmin)->put('/admin/refugios/' . $refugio->ID_Usuario . '/validar');

        $this->assertEquals('Validado', $refugio->fresh()->Estado_Validacion);
        Mail::assertSent(RefugioDadoDeAlta::class, 1);

        // Ahora sí puede iniciar sesión
        $this->post('/salir');
        $this->post('/ingresar', ['email' => $refugio->usuario->Email, 'password' => UsuarioFactory::CONTRASEÑA])
            ->assertRedirect('/cuenta');
    }

    public function test_cp18_rechazar_exige_el_motivo()
    {
        $refugio = Refugio::factory()->create();

        $this->withSession($this->sesionAdmin)
            ->put('/admin/refugios/' . $refugio->ID_Usuario . '/rechazar', ['motivo' => ''])
            ->assertSessionHas('error');

        $this->assertEquals('Pendiente de validación', $refugio->fresh()->Estado_Validacion);
    }

    public function test_cp18_rechazado_guarda_el_motivo_y_no_puede_iniciar_sesion()
    {
        $refugio = Refugio::factory()->create();

        $this->withSession($this->sesionAdmin)
            ->put('/admin/refugios/' . $refugio->ID_Usuario . '/rechazar', ['motivo' => 'El comprobante no es legible']);

        $refugio = $refugio->fresh();
        $this->assertEquals('Rechazado', $refugio->Estado_Validacion);
        $this->assertEquals('El comprobante no es legible', $refugio->Motivo_Rechazo);

        $this->post('/salir');
        $this->post('/ingresar', ['email' => $refugio->usuario->Email, 'password' => UsuarioFactory::CONTRASEÑA])
            ->assertSessionHas('error');
    }

    public function test_solo_se_resuelve_una_solicitud_pendiente()
    {
        // Un refugio ya validado no puede volver a validarse ni pasar a Rechazado (13.2.1)
        $refugio = Refugio::factory()->validado()->create();

        $this->withSession($this->sesionAdmin)
            ->put('/admin/refugios/' . $refugio->ID_Usuario . '/rechazar', ['motivo' => 'Cualquiera'])
            ->assertSessionHas('error');

        $this->assertEquals('Validado', $refugio->fresh()->Estado_Validacion);
    }

    public function test_solo_el_administrador_puede_validar()
    {
        $refugio = Refugio::factory()->create();
        $sesionParticular = ['id_usuario' => 99, 'tipo' => 'Particular', 'nombre' => 'Ana'];

        $this->withSession($sesionParticular)
            ->put('/admin/refugios/' . $refugio->ID_Usuario . '/validar')
            ->assertForbidden();

        // Sin sesión (flushSession borra la sesión de la prueba)
        $this->flushSession();
        $this->put('/admin/refugios/' . $refugio->ID_Usuario . '/validar')->assertRedirect('/ingresar');

        $this->assertEquals('Pendiente de validación', $refugio->fresh()->Estado_Validacion);
    }
}
