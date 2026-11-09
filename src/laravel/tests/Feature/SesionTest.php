<?php

namespace Tests\Feature;

use App\Http\Controllers\SesionController;
use App\Models\Administrador;
use App\Models\Particular;
use App\Models\Refugio;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Inicio de sesión (RF:0022) y cierre de sesión (RF:0023). Casos CP-04 y CP-05.
 * Los casos d) y e) de CP-04 (correo sin confirmar, cuenta inhabilitada) son del Sprint 5.
 */
class SesionTest extends TestCase
{
    use RefreshDatabase;

    private function ingresar($email, $password = UsuarioFactory::CONTRASEÑA)
    {
        return $this->post('/ingresar', ['email' => $email, 'password' => $password]);
    }

    public function test_el_formulario_de_ingreso_se_muestra()
    {
        $this->get('/ingresar')->assertOk()->assertSee('Ingresar');
    }

    public function test_cp04_a_el_particular_entra_a_su_cuenta()
    {
        $particular = Particular::factory()->create();

        $this->ingresar($particular->usuario->Email)->assertRedirect('/cuenta');
        $this->assertEquals('Particular', session('tipo'));
    }

    public function test_cp04_a_el_refugio_entra_a_su_cuenta()
    {
        $refugio = Refugio::factory()->validado()->create();

        $this->ingresar($refugio->usuario->Email)->assertRedirect('/cuenta');
        $this->assertEquals('Refugio', session('tipo'));
    }

    public function test_cp04_a_el_administrador_entra_al_panel()
    {
        $administrador = Administrador::factory()->create();

        $this->ingresar($administrador->usuario->Email)->assertRedirect('/admin');
        $this->assertEquals('Administrador', session('tipo'));
    }

    public function test_cp04_b_y_c_mismo_mensaje_con_contrasena_incorrecta_o_correo_inexistente()
    {
        $particular = Particular::factory()->create();

        $this->ingresar($particular->usuario->Email, 'incorrecta')
            ->assertSessionHas('error', SesionController::MENSAJE_ERROR);

        $this->ingresar('no-existe@prueba.uy')
            ->assertSessionHas('error', SesionController::MENSAJE_ERROR);

        $this->assertNull(session('id_usuario'));
    }

    public function test_cp05_cerrar_sesion_vuelve_al_inicio_y_las_paginas_privadas_piden_ingresar()
    {
        $particular = Particular::factory()->create();
        $this->ingresar($particular->usuario->Email);

        $this->post('/salir')->assertRedirect('/');

        $this->assertNull(session('id_usuario'));
        $this->get('/cuenta')->assertRedirect('/ingresar');
    }
}
