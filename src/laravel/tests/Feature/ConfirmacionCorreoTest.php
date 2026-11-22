<?php

namespace Tests\Feature;

use App\Http\Controllers\ConfirmacionCorreoController;
use App\Http\Controllers\SesionController;
use App\Mail\ConfirmarCorreo;
use App\Models\Particular;
use App\Models\Usuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// Confirmación del correo del Particular (RF:0043, RF:0063)
class ConfirmacionCorreoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    // Registra a Ana con el formulario y devuelve el enlace que llegó en el correo
    private function registrarYObtenerEnlace()
    {
        $this->post('/registro', [
            'ci' => '12345672',
            'nombre' => 'Ana',
            'apellido' => 'Suárez',
            'telefono' => '099123456',
            'email' => 'ana@prueba.uy',
            'password' => 'clave-segura',
        ]);

        $enlace = null;
        Mail::assertSent(ConfirmarCorreo::class, function ($correo) use (&$enlace)
        {
            $enlace = $correo->enlace;
            return $correo->hasTo('ana@prueba.uy');
        });

        return $enlace;
    }

    private function ingresar($email, $password)
    {
        return $this->post('/ingresar', ['email' => $email, 'password' => $password]);
    }

    public function test_cp03_al_registrarse_llega_un_unico_correo_y_la_cuenta_queda_sin_confirmar()
    {
        $this->registrarYObtenerEnlace();

        Mail::assertSent(ConfirmarCorreo::class, 1);
        $this->assertNull(Usuario::where('Email', 'ana@prueba.uy')->first()->Email_Verificado);
    }

    public function test_cp04_d_sin_confirmar_no_ingresa_y_ve_el_mensaje_generico()
    {
        $this->registrarYObtenerEnlace();

        $this->ingresar('ana@prueba.uy', 'clave-segura')
            ->assertSessionHas('error', SesionController::MENSAJE_ERROR);
        $this->assertNull(session('id_usuario'));
    }

    public function test_cp03_el_enlace_valido_confirma_y_despues_puede_ingresar()
    {
        $enlace = $this->registrarYObtenerEnlace();

        $this->get($enlace)->assertRedirect('/ingresar')->assertSessionHas('exito');
        $this->assertNotNull(Usuario::where('Email', 'ana@prueba.uy')->first()->Email_Verificado);

        $this->ingresar('ana@prueba.uy', 'clave-segura')->assertRedirect('/cuenta');
    }

    public function test_cp03_un_enlace_alterado_se_rechaza()
    {
        $enlace = $this->registrarYObtenerEnlace();
        $otro = Particular::factory()->create(['ID_Usuario' => Usuario::factory()->sinConfirmar()]);
        $ana = Usuario::where('Email', 'ana@prueba.uy')->first();

        // Se cambia el número de usuario del enlace: la firma ya no coincide
        $alterado = str_replace('/confirmar-correo/' . $ana->ID_Usuario, '/confirmar-correo/' . $otro->ID_Usuario, $enlace);
        $this->get($alterado)->assertRedirect('/ingresar')->assertSessionHas('error');

        // Sin firma tampoco sirve
        $this->get('/confirmar-correo/' . $ana->ID_Usuario)->assertSessionHas('error');

        $this->assertNull($ana->fresh()->Email_Verificado);
        $this->assertNull($otro->usuario->fresh()->Email_Verificado);
    }

    public function test_reenviar_manda_otro_correo_solo_a_una_cuenta_sin_confirmar_con_el_mismo_mensaje()
    {
        $sinConfirmar = Particular::factory()->create(['ID_Usuario' => Usuario::factory()->sinConfirmar()]);
        $confirmado = Particular::factory()->create();

        // Sin confirmar: le llega
        $this->post('/confirmar-correo', ['email' => $sinConfirmar->usuario->Email])
            ->assertSessionHas('exito', ConfirmacionCorreoController::MENSAJE_REENVIO);
        Mail::assertSent(ConfirmarCorreo::class, 1);

        // Ya confirmado o inexistente: no se manda nada, pero el mensaje es el mismo
        $this->post('/confirmar-correo', ['email' => $confirmado->usuario->Email])
            ->assertSessionHas('exito', ConfirmacionCorreoController::MENSAJE_REENVIO);
        $this->post('/confirmar-correo', ['email' => 'nadie@prueba.uy'])
            ->assertSessionHas('exito', ConfirmacionCorreoController::MENSAJE_REENVIO);
        Mail::assertSent(ConfirmarCorreo::class, 1);
    }

    public function test_el_refugio_y_el_administrador_ingresan_sin_confirmacion_por_correo()
    {
        // La regla es solo del Particular: estas cuentas no tienen formulario de confirmación
        $this->seed();

        $this->ingresar('refugio@adoptauy.test', UsuarioFactory::CONTRASEÑA)->assertRedirect('/cuenta');
        $this->flushSession();
        $this->ingresar('admin@adoptauy.test', UsuarioFactory::CONTRASEÑA)->assertRedirect('/admin');
    }
}
