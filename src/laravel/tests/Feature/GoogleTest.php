<?php

namespace Tests\Feature;

use App\Http\Controllers\GoogleController;
use App\Http\Controllers\SesionController;
use App\Models\Administrador;
use App\Models\Particular;
use App\Models\Refugio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Tests\TestCase;

/**
 * Continuar con Google (RF:0066, RNE27). Caso CP-49.
 * Google se simula con un mock de Socialite: ninguna prueba llama a Google.
 */
class GoogleTest extends TestCase
{
    use RefreshDatabase;

    // Hace que Socialite "devuelva" esta cuenta de Google al volver al callback
    private function simularGoogle($cambios = [])
    {
        $datos = array_merge([
            'sub' => 'google-123',
            'email' => 'lucia@gmail.com',
            'email_verified' => true,
            'given_name' => 'Lucía',
            'family_name' => 'Pereira',
            'name' => 'Lucía Pereira',
        ], $cambios);

        $google = new UsuarioGoogle();
        $google->setRaw($datos)->map([
            'id' => $datos['sub'],
            'email' => $datos['email'],
            'name' => $datos['name'],
        ]);

        Socialite::shouldReceive('driver->user')->andReturn($google);
    }

    private function datosCompletar($cambios = [])
    {
        return array_merge([
            'ci' => '12345672',
            'telefono' => '099123456',
            'nombre' => 'Lucía',
            'apellido' => 'Pereira',
        ], $cambios);
    }

    public function test_el_boton_aparece_en_ingresar_y_en_crear_cuenta()
    {
        $this->get('/ingresar')->assertSee('Continuar con Google');
        $this->get('/registro')->assertSee('Continuar con Google');
    }

    public function test_el_boton_manda_a_google()
    {
        config(['services.google.client_id' => 'id-de-prueba', 'services.google.redirect' => 'https://localhost:8443/auth/google/callback']);

        $respuesta = $this->get('/auth/google');

        $respuesta->assertRedirect();
        $this->assertStringStartsWith('https://accounts.google.com/', $respuesta->headers->get('Location'));
    }

    // ---------- Cuenta nueva ----------

    public function test_cp49_una_cuenta_nueva_pide_ci_y_telefono_con_el_nombre_precargado()
    {
        $this->simularGoogle();

        $this->get('/auth/google/callback')->assertRedirect('/auth/google/completar');

        $this->get('/auth/google/completar')
            ->assertOk()
            ->assertSee('lucia@gmail.com')
            ->assertSee('value="Lucía"', false)
            ->assertSee('value="Pereira"', false);

        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_cp49_al_completar_se_crea_el_particular_sin_contrasena_y_con_el_correo_confirmado()
    {
        $this->simularGoogle();
        $this->get('/auth/google/callback');

        $this->post('/auth/google/completar', $this->datosCompletar())->assertRedirect('/cuenta');

        $usuario = Usuario::where('Email', 'lucia@gmail.com')->first();
        $this->assertEquals('Particular', $usuario->Tipo_Usuario);
        $this->assertEquals('google-123', $usuario->Google_ID);
        $this->assertNull($usuario->Contraseña);
        $this->assertNotNull($usuario->Email_Verificado);
        $this->assertEquals('12345672', $usuario->particular->CI);

        // Quedó con la sesión iniciada
        $this->assertEquals('Particular', session('tipo'));
    }

    public function test_cp49_una_ci_invalida_o_repetida_no_crea_la_cuenta()
    {
        $this->simularGoogle();
        $this->get('/auth/google/callback');

        $this->post('/auth/google/completar', $this->datosCompletar(['ci' => '12345673']))->assertSessionHasErrors('ci');

        Particular::factory()->create(['CI' => '12345672']);
        $this->post('/auth/google/completar', $this->datosCompletar())->assertSessionHasErrors('ci');

        $this->assertNull(Usuario::where('Email', 'lucia@gmail.com')->first());
    }

    public function test_cp49_un_telefono_invalido_no_crea_la_cuenta()
    {
        $this->simularGoogle();
        $this->get('/auth/google/callback');

        $this->post('/auth/google/completar', $this->datosCompletar(['telefono' => '1234567']))->assertSessionHasErrors('telefono');

        $this->assertNull(Usuario::where('Email', 'lucia@gmail.com')->first());
    }

    public function test_completar_sin_pasar_por_google_vuelve_a_ingresar()
    {
        $this->get('/auth/google/completar')->assertRedirect('/ingresar');
        $this->post('/auth/google/completar', $this->datosCompletar())->assertRedirect('/ingresar');

        $this->assertDatabaseCount('Usuario', 0);
    }

    // ---------- Cuenta existente ----------

    public function test_cp49_el_correo_de_un_particular_activo_vincula_la_cuenta_y_confirma_el_correo()
    {
        $particular = Particular::factory()->create();
        $usuario = $particular->usuario;
        $this->assertNull($usuario->Email_Verificado);

        $this->simularGoogle(['email' => $usuario->Email]);

        $this->get('/auth/google/callback')->assertRedirect('/cuenta');

        $usuario->refresh();
        $this->assertEquals('google-123', $usuario->Google_ID);
        $this->assertNotNull($usuario->Email_Verificado);
        $this->assertNotNull($usuario->Contraseña);   // conserva su contraseña
        $this->assertEquals($usuario->ID_Usuario, session('id_usuario'));
    }

    public function test_cp49_una_cuenta_ya_vinculada_entra_aunque_cambie_el_correo_en_google()
    {
        $particular = Particular::factory()->create();
        $particular->usuario->Google_ID = 'google-123';
        $particular->usuario->save();

        $this->simularGoogle(['email' => 'otro-correo@gmail.com']);

        $this->get('/auth/google/callback')->assertRedirect('/cuenta');
        $this->assertEquals($particular->ID_Usuario, session('id_usuario'));
        $this->assertDatabaseCount('Usuario', 1);
    }

    public function test_cp49_el_correo_de_un_refugio_o_del_administrador_da_el_mensaje_generico()
    {
        foreach ([Refugio::factory()->validado()->create()->usuario, Administrador::factory()->create()->usuario] as $usuario)
        {
            $this->simularGoogle(['email' => $usuario->Email]);

            $this->get('/auth/google/callback')
                ->assertRedirect('/ingresar')
                ->assertSessionHas('error', GoogleController::MENSAJE_ERROR);

            $this->assertNull($usuario->fresh()->Google_ID);
            $this->assertNull(session('id_usuario'));
        }
    }

    public function test_cp49_una_cuenta_inhabilitada_o_dada_de_baja_da_el_mensaje_generico()
    {
        foreach (['Inhabilitado', 'Baja'] as $estado)
        {
            $usuario = Particular::factory()->create()->usuario;
            $usuario->Estado_Usuario = $estado;
            $usuario->save();

            $this->simularGoogle(['sub' => 'google-' . $estado, 'email' => $usuario->Email]);

            $this->get('/auth/google/callback')->assertSessionHas('error', GoogleController::MENSAJE_ERROR);
            $this->assertNull($usuario->fresh()->Google_ID);
            $this->assertNull(session('id_usuario'));
        }
    }

    // ---------- Respuestas de Google que no sirven ----------

    public function test_cp49_cancelar_en_google_o_un_state_invalido_no_crea_ni_modifica_nada()
    {
        Socialite::shouldReceive('driver->user')->andThrow(new InvalidStateException());

        $this->get('/auth/google/callback')
            ->assertRedirect('/ingresar')
            ->assertSessionHas('error', GoogleController::MENSAJE_ERROR);

        $this->assertDatabaseCount('Usuario', 0);
        $this->assertNull(session('google_pendiente'));
    }

    public function test_cp49_una_cuenta_de_google_sin_correo_verificado_se_rechaza()
    {
        $this->simularGoogle(['email_verified' => false]);

        $this->get('/auth/google/callback')->assertSessionHas('error', GoogleController::MENSAJE_ERROR);

        $this->assertNull(session('google_pendiente'));
        $this->assertDatabaseCount('Usuario', 0);
    }

    // ---------- Contraseña ----------

    public function test_una_cuenta_creada_con_google_no_entra_con_el_formulario_hasta_definir_contrasena()
    {
        $this->simularGoogle();
        $this->get('/auth/google/callback');
        $this->post('/auth/google/completar', $this->datosCompletar());
        $this->post('/salir');

        $this->post('/ingresar', ['email' => 'lucia@gmail.com', 'password' => 'cualquiera'])
            ->assertSessionHas('error', SesionController::MENSAJE_ERROR);
        $this->assertNull(session('id_usuario'));
    }
}
