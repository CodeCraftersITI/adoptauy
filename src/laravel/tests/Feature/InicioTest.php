<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Página de inicio pública (RF:0040, RF:0048).
 * Base de CP-08: los listados por estado se prueban cuando existan las publicaciones.
 */
class InicioTest extends TestCase
{
    public function test_un_invitado_ve_la_pagina_de_inicio_con_el_layout()
    {
        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertSee('Adoptar cambia dos vidas.');
        $respuesta->assertSee('AdoptaUY · CodeCrafters · ITI CETP-UTU');   // viene del partial pie
        $respuesta->assertSee('Ingresar');                                  // viene del partial acciones-usuario
    }

    public function test_con_sesion_iniciada_se_ve_cerrar_sesion()
    {
        $this->withSession(['id_usuario' => 1, 'tipo' => 'Particular', 'nombre' => 'Ana'])
            ->get('/')
            ->assertSee('Cerrar sesión')
            ->assertSee('Mi cuenta');
    }
}
