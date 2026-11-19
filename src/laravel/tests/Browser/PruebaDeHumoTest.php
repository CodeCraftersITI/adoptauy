<?php

namespace Tests\Browser;

use App\Http\Middleware\AntiBot;
use App\Models\Particular;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Prueba de humo en Chrome (plan de pruebas, sección 7): CP-01 registro, CP-04 inicio de sesión
 * y CP-06 alta de publicación, más CP-05 cierre de sesión. Corre contra adoptauy_test.
 * Los formularios con freno contra bots (D99) esperan unos segundos antes de enviar,
 * como lo haría una persona.
 */
class PruebaDeHumoTest extends DuskTestCase
{
    use DatabaseMigrations;

    // Un poco más que el mínimo del freno contra bots (3 segundos)
    private const ESPERA_HUMANA = (AntiBot::SEGUNDOS_MINIMOS + 1) * 1000;

    private function ingresar(Browser $browser, $email)
    {
        $browser->visit('/ingresar')
            ->type('email', $email)
            ->type('password', UsuarioFactory::CONTRASEÑA)
            ->press('Ingresar');
    }

    public function test_cp01_registro_de_particular()
    {
        $this->browse(function (Browser $browser)
        {
            $browser->visit('/registro')
                ->type('ci', '12345672')
                ->type('telefono', '099123456')
                ->type('nombre', 'Ana')
                ->type('apellido', 'Suárez')
                ->type('email', 'ana@prueba.uy')
                ->type('password', 'clave-segura')
                ->screenshot('cp01-1-formulario-de-registro-completo')
                ->pause(self::ESPERA_HUMANA)
                ->press('Crear cuenta')
                ->assertPathIs('/ingresar')
                ->assertSee('Tu cuenta fue creada')
                ->screenshot('cp01-2-cuenta-creada');
        });

        $this->assertDatabaseHas('Usuario', ['Email' => 'ana@prueba.uy', 'Tipo_Usuario' => 'Particular']);
    }

    public function test_el_campo_de_cedula_no_acepta_letras()
    {
        $this->browse(function (Browser $browser)
        {
            $browser->visit('/registro')
                ->type('ci', '1a2b3c4d5')
                ->assertInputValue('ci', '12345')
                ->screenshot('cp02-cedula-sin-letras');
        });
    }

    public function test_cp04_inicio_de_sesion_y_cp05_cierre()
    {
        $particular = Particular::factory()->create();

        $this->browse(function (Browser $browser) use ($particular)
        {
            $this->ingresar($browser, $particular->usuario->Email);

            $browser->assertPathIs('/cuenta')
                ->assertSee('Mi cuenta')
                ->screenshot('cp04-1-ingreso-a-mi-cuenta')
                ->click('.nav-acciones button[aria-label="Cerrar sesión"]')
                ->assertPathIs('/')
                ->assertSee('Cerraste sesión')
                ->screenshot('cp05-1-sesion-cerrada');

            // Sin sesión, Mi cuenta pide ingresar
            $browser->visit('/cuenta')->assertPathIs('/ingresar')
                ->screenshot('cp05-2-sin-sesion-pide-ingresar');
        });
    }

    public function test_cp04_datos_incorrectos_muestran_el_mensaje_generico()
    {
        $this->browse(function (Browser $browser)
        {
            $browser->visit('/ingresar')
                ->type('email', 'nadie@prueba.uy')
                ->type('password', 'incorrecta')
                ->press('Ingresar')
                ->assertPathIs('/ingresar')
                ->assertSee('El correo o la contraseña no son correctos.')
                ->screenshot('cp04-2-mensaje-generico');
        });
    }

    public function test_cp06_alta_de_publicacion_en_adopcion()
    {
        $particular = Particular::factory()->create();
        // Foto de prueba: una imagen de un color, creada con GD y guardada como .jpg común
        // (la de UploadedFile::fake() es temporal y se borra antes de que Chrome la suba)
        $foto = sys_get_temp_dir() . '/perro-dusk.jpg';
        $imagen = imagecreatetruecolor(800, 600);
        imagefill($imagen, 0, 0, imagecolorallocate($imagen, 176, 58, 105));
        imagejpeg($imagen, $foto);

        $this->browse(function (Browser $browser) use ($particular, $foto)
        {
            $this->ingresar($browser, $particular->usuario->Email);

            $browser->visit('/publicar')
                ->select('especie', 'Perro')
                ->type('raza', 'Mestizo')
                ->select('sexo', 'Macho')
                ->select('tamanio', 'Mediano')
                ->type('edad', '8')
                ->select('edad_unidad', 'meses')
                ->select('esterilizacion', 'Esterilizado')
                ->type('ubicacion', 'Pocitos')
                ->type('salud', 'Vacunas al día')
                ->attach('fotos[]', $foto)
                ->screenshot('cp06-1-formulario-de-publicacion-completo')
                ->pause(self::ESPERA_HUMANA)
                ->press('Publicar')
                ->assertPathIs('/cuenta')
                ->assertSee('Tu publicación ya está en adopción')
                ->screenshot('cp06-2-publicacion-en-adopcion');
        });

        $this->assertDatabaseHas('Publicacion_Animal', ['ID_Usuario' => $particular->ID_Usuario, 'Raza' => 'Mestizo']);
        $this->assertDatabaseHas('Publicacion_Adopcion', ['Edad_Meses' => 8, 'Ubicacion_Aproximada' => 'Pocitos']);
    }
}
