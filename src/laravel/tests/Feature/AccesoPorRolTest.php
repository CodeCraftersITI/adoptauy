<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acceso por rol a los paneles (RF:0022, middleware VerificarRol).
 * Roles: Invitado, Particular, Refugio y Administrador.
 * withSession() simula la sesión que guarda SesionController al iniciar sesión.
 */
class AccesoPorRolTest extends TestCase
{
    use RefreshDatabase;

    private function sesionDe($tipo)
    {
        return ['id_usuario' => 1, 'tipo' => $tipo, 'nombre' => 'Nombre de prueba'];
    }

    public function test_el_invitado_es_enviado_a_ingresar()
    {
        $this->get('/cuenta')->assertRedirect('/ingresar');
        $this->get('/admin')->assertRedirect('/ingresar');
    }

    public function test_el_particular_entra_a_mi_cuenta_pero_no_al_panel_de_administracion()
    {
        $this->withSession($this->sesionDe('Particular'))->get('/cuenta')->assertOk()->assertSee('Nombre de prueba');
        $this->withSession($this->sesionDe('Particular'))->get('/admin')->assertForbidden();
    }

    public function test_el_refugio_entra_a_mi_cuenta_pero_no_al_panel_de_administracion()
    {
        $this->withSession($this->sesionDe('Refugio'))->get('/cuenta')->assertOk();
        $this->withSession($this->sesionDe('Refugio'))->get('/admin')->assertForbidden();
    }

    public function test_el_administrador_entra_al_panel_pero_no_a_mi_cuenta()
    {
        $this->withSession($this->sesionDe('Administrador'))->get('/admin')->assertOk();
        $this->withSession($this->sesionDe('Administrador'))->get('/cuenta')->assertForbidden();
    }
}
