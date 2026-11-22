<?php

namespace Tests\Feature;

use App\Http\Middleware\AntiBot;
use App\Models\Particular;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Freno contra bots en los formularios (pedido del docente): tiempo mínimo y campo trampa
 * (middleware AntiBot) y límite de envíos por minuto (throttle). El resto de las pruebas lo
 * tiene apagado (ANTIBOT_ACTIVO=false en phpunit.xml); acá se prende.
 */
class AntiBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.antibot' => true]);
        $this->travelTo(now()->setDate(2026, 11, 16)->setTime(10, 0, 0));
    }

    // Datos válidos de registro, con los campos ocultos como los manda el formulario
    private function registro($cambios = [], $email = 'ana@prueba.uy')
    {
        return array_merge([
            'ci' => '12345672',
            'nombre' => 'Ana',
            'apellido' => 'Suárez',
            'telefono' => '099123456',
            'email' => $email,
            'password' => 'clave-segura',
            'formulario_inicio' => encrypt(time() - 10),   // abierto hace 10 segundos
            'sitio_web' => '',
        ], $cambios);
    }

    public function test_el_formulario_trae_los_campos_ocultos()
    {
        $this->get('/registro')->assertSee('name="formulario_inicio"', false)->assertSee('name="sitio_web"', false);
    }

    public function test_una_persona_que_tarda_unos_segundos_se_registra()
    {
        $this->post('/registro', $this->registro())->assertRedirect('/ingresar')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Usuario', 1);
    }

    public function test_enviado_en_menos_de_3_segundos_se_rechaza()
    {
        $this->post('/registro', $this->registro(['formulario_inicio' => encrypt(time() - 1)]))
            ->assertSessionHas('error', AntiBot::MENSAJE);
        $this->assertDatabaseCount('Usuario', 0);

        // Justo 3 segundos: ya se acepta
        $this->post('/registro', $this->registro(['formulario_inicio' => encrypt(time() - AntiBot::SEGUNDOS_MINIMOS)]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Usuario', 1);
    }

    public function test_el_campo_trampa_lleno_se_rechaza()
    {
        $this->post('/registro', $this->registro(['sitio_web' => 'http://spam.example']))
            ->assertSessionHas('error', AntiBot::MENSAJE);
        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_sin_la_hora_o_con_la_hora_cambiada_se_rechaza()
    {
        $this->post('/registro', $this->registro(['formulario_inicio' => null]))->assertSessionHas('error', AntiBot::MENSAJE);
        $this->post('/registro', $this->registro(['formulario_inicio' => (string) (time() - 60)]))->assertSessionHas('error', AntiBot::MENSAJE);
        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_al_rechazar_no_se_devuelve_la_contrasena()
    {
        $this->post('/registro', $this->registro(['sitio_web' => 'spam']));

        $this->assertEquals('Ana', session()->getOldInput('nombre'));
        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_mas_de_5_registros_por_minuto_desde_la_misma_ip_da_429()
    {
        for ($i = 1; $i <= 5; $i++)
        {
            // Datos inválidos a propósito: igual cuentan como envíos
            $this->post('/registro', $this->registro(['ci' => '1']))->assertStatus(302);
        }

        $this->post('/registro', $this->registro())->assertStatus(429)->assertSee('Demasiados intentos');

        // Pasado el minuto se puede de nuevo
        $this->travel(61)->seconds();
        $this->post('/registro', $this->registro(['formulario_inicio' => encrypt(time() - 10)]))->assertStatus(302);
    }

    public function test_cada_formulario_tiene_su_propio_limite()
    {
        // Agotar el cupo del registro no impide iniciar sesión
        for ($i = 1; $i <= 6; $i++)
        {
            $this->post('/registro', $this->registro(['ci' => '1']));
        }

        $particular = Particular::factory()->create();
        $this->post('/ingresar', ['email' => $particular->usuario->Email, 'password' => 'adoptauy-prueba'])
            ->assertRedirect('/cuenta');
    }

    public function test_mas_de_10_intentos_de_ingreso_por_minuto_da_429()
    {
        for ($i = 1; $i <= 10; $i++)
        {
            $this->post('/ingresar', ['email' => 'nadie@prueba.uy', 'password' => 'incorrecta'])->assertStatus(302);
        }

        $this->post('/ingresar', ['email' => 'nadie@prueba.uy', 'password' => 'incorrecta'])->assertStatus(429);
    }
}
