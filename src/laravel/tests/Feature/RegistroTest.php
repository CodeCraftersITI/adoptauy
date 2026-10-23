<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Registro de Particular (RF:0039). Casos CP-01 y CP-02.
 */
class RegistroTest extends TestCase
{
    use RefreshDatabase;

    // Datos válidos de CP-01 (ficticios). Con $cambios se reemplaza algún campo.
    private function datos($cambios = [])
    {
        return array_merge([
            'ci' => '1.234.567-2',
            'nombre' => 'Ana',
            'apellido' => 'Suárez',
            'telefono' => '099 123 456',
            'email' => 'ana@prueba.uy',
            'password' => 'clave-segura',
        ], $cambios);
    }

    public function test_el_formulario_de_registro_se_muestra()
    {
        $this->get('/registro')->assertOk()->assertSee('Crear cuenta');
    }

    public function test_cp01_registro_valido_crea_usuario_y_particular()
    {
        $respuesta = $this->post('/registro', $this->datos());

        $respuesta->assertRedirect('/ingresar');

        $usuario = Usuario::where('Email', 'ana@prueba.uy')->first();
        $this->assertEquals('Particular', $usuario->Tipo_Usuario);

        // La cédula y el teléfono se guardan solo con números
        $this->assertDatabaseHas('Particular', [
            'ID_Usuario' => $usuario->ID_Usuario,
            'CI' => '12345672',
            'Telefono' => '099123456',
        ]);
    }

    public function test_rnf0007_la_contrasena_se_guarda_como_hash()
    {
        $this->post('/registro', $this->datos());

        $usuario = Usuario::where('Email', 'ana@prueba.uy')->first();
        $this->assertNotEquals('clave-segura', $usuario->Contraseña);
        $this->assertTrue(Hash::check('clave-segura', $usuario->Contraseña));
    }

    public function test_cp02_a_rechaza_sin_telefono()
    {
        $this->post('/registro', $this->datos(['telefono' => '']))->assertSessionHasErrors('telefono');
        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_cp02_c_rechaza_correo_sin_arroba()
    {
        $this->post('/registro', $this->datos(['email' => 'ana.prueba.uy']))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_cp02_d_rechaza_cedula_con_digito_verificador_incorrecto()
    {
        $this->post('/registro', $this->datos(['ci' => '1.234.567-3']))->assertSessionHasErrors('ci');
        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_cp02_f_rechaza_contrasena_de_7_caracteres()
    {
        $this->post('/registro', $this->datos(['password' => '1234567']))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_cp02_b_rechaza_correo_ya_registrado()
    {
        $this->post('/registro', $this->datos());
        $this->post('/registro', $this->datos(['ci' => '1234561']))->assertSessionHasErrors('email');

        $this->assertDatabaseCount('Usuario', 1);
    }

    public function test_cp02_e_rechaza_cedula_ya_registrada()
    {
        $this->post('/registro', $this->datos());
        $this->post('/registro', $this->datos(['email' => 'otra@prueba.uy']))->assertSessionHasErrors('ci');

        $this->assertDatabaseCount('Particular', 1);
    }

    public function test_no_se_puede_registrar_un_administrador_desde_el_formulario()
    {
        // Aunque se mande Tipo_Usuario, el registro siempre crea un Particular ($fillable)
        $this->post('/registro', $this->datos(['Tipo_Usuario' => 'Administrador']));

        $this->assertEquals('Particular', Usuario::first()->Tipo_Usuario);
    }
}
