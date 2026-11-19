<?php

namespace Tests\Browser;

use App\Models\Particular;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Lo que solo se puede probar en un navegador: el JavaScript del formulario único de publicar
 * y la vista en el celular a 360 px sin desplazamiento horizontal (CP-39, RNF:0003).
 */
class PantallasTest extends DuskTestCase
{
    use DatabaseMigrations;

    // Ancho total de la página: si es mayor que la pantalla, hay desplazamiento horizontal
    private function anchoDeLaPagina(Browser $browser)
    {
        return $browser->script('return document.documentElement.scrollWidth;')[0];
    }

    public function test_el_formulario_de_publicar_muestra_los_campos_del_tipo_elegido()
    {
        $particular = Particular::factory()->create();

        $this->browse(function (Browser $browser) use ($particular)
        {
            $browser->visit('/ingresar')
                ->type('email', $particular->usuario->Email)
                ->type('password', UsuarioFactory::CONTRASEÑA)
                ->press('Ingresar')
                ->visit('/publicar')
                // Al abrir: En adopción, con tamaño y edad, sin zona ni contacto
                ->assertVisible('select[name="tamanio"]')
                ->assertMissing('input[name="zona"]')
                ->screenshot('cp06-3-publicar-en-adopcion')
                // Mascota perdida: aparecen los datos del extravío y se van los de adopción
                ->click('label.pestana:nth-child(2)')
                ->assertVisible('input[name="zona"]')
                ->assertVisible('input[name="contacto"]')
                ->assertMissing('select[name="tamanio"]')
                ->assertSee('Fecha del extravío')
                ->screenshot('cp09-publicar-mascota-perdida')
                // Mascota encontrada: el nombre pasa a ser opcional
                ->click('label.pestana:nth-child(3)')
                ->assertSee('Fecha en que la encontraste')
                ->assertSee('(opcional)')
                ->screenshot('cp47-publicar-mascota-encontrada');
        });
    }

    public function test_cp39_a_360_px_no_hay_desplazamiento_horizontal()
    {
        $particular = Particular::factory()->create();

        $this->browse(function (Browser $browser) use ($particular)
        {
            $browser->resize(360, 780);

            foreach (['/', '/nosotros', '/adopcion', '/perdidas', '/encontradas', '/refugios', '/ingresar', '/registro'] as $pagina)
            {
                $browser->visit($pagina);
                $this->assertLessThanOrEqual(360, $this->anchoDeLaPagina($browser), 'Desplazamiento horizontal en ' . $pagina);
                $browser->screenshot('cp39-360px' . ($pagina == '/' ? '-inicio' : str_replace('/', '-', $pagina)));
            }

            // Con sesión: Mi cuenta, Mi perfil y Publicar
            $browser->visit('/ingresar')
                ->type('email', $particular->usuario->Email)
                ->type('password', UsuarioFactory::CONTRASEÑA)
                ->press('Ingresar');

            foreach (['/cuenta', '/perfil', '/publicar'] as $pagina)
            {
                $browser->visit($pagina);
                $this->assertLessThanOrEqual(360, $this->anchoDeLaPagina($browser), 'Desplazamiento horizontal en ' . $pagina);
                $browser->screenshot('cp39-360px' . str_replace('/', '-', $pagina));
            }
        });
    }

    public function test_en_el_celular_el_menu_se_abre_y_muestra_los_enlaces()
    {
        $this->browse(function (Browser $browser)
        {
            $browser->resize(360, 780)
                ->visit('/')
                ->assertMissing('.menu-movil-panel')
                ->click('.menu-movil summary')
                ->assertVisible('.menu-movil-panel')
                ->assertSeeIn('.menu-movil-panel', 'Refugios')
                ->assertSeeIn('.menu-movil-panel', 'Ingresar')
                ->screenshot('cp39-menu-del-celular');
        });
    }
}
