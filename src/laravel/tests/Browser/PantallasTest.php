<?php

namespace Tests\Browser;

use App\Models\Administrador;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\Refugio;
use App\Models\RefugioDatoContacto;
use App\Models\Reporte;
use App\Models\SolicitudAdopcion;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

// Lo que solo se puede probar en un navegador
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
                // press() no espera la página nueva (DEF-11)
                ->waitForLocation('/cuenta')
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

    // Crea una mascota en adopción con una foto (los datos de la tarjeta y del detalle)
    private function crearAdopcion($idUsuario)
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Perro', 'Raza' => 'Mestizo', 'Sexo' => 'Hembra']);
        $publicacion->ID_Usuario = $idUsuario;
        $publicacion->Tipo_Publicacion = 'Adopción';
        $publicacion->save();

        PublicacionAdopcion::create([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Edad_Meses' => 30,
            'Tamaño' => 'Mediano',
            'Estado_Salud' => 'Sana, con las vacunas al día',
            'Estado_Esterilizacion' => 'Esterilizado',
            'Ubicacion_Aproximada' => 'Pocitos',
        ]);
        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/prueba.jpg']);

        return $publicacion;
    }

    private function ingresar(Browser $browser, $email, $destino = '/cuenta')
    {
        $browser->driver->manage()->deleteAllCookies();
        $browser->visit('/ingresar')
            ->type('email', $email)
            ->type('password', UsuarioFactory::CONTRASEÑA)
            ->press('Ingresar')
            ->waitForLocation($destino);
    }

    // Visita cada página y anota las que son más anchas que la pantalla
    private function revisarPaginas(Browser $browser, array $paginas, array &$desbordadas)
    {
        foreach ($paginas as $pagina)
        {
            $browser->visit($pagina);

            // Si redirigió (por ejemplo, al ingreso) no se revisó la página pedida
            $this->assertEquals(parse_url($pagina, PHP_URL_PATH), parse_url($browser->driver->getCurrentURL(), PHP_URL_PATH), 'No se pudo abrir ' . $pagina);
            $browser->assertDontSee('Server Error');

            $browser->screenshot('cp39-360px' . ($pagina == '/' ? '-inicio' : preg_replace('/[^a-z0-9]+/', '-', $pagina)));

            $ancho = $this->anchoDeLaPagina($browser);
            if ($ancho > 360)
            {
                $desbordadas[] = $pagina . ' (' . $ancho . ' px)';
            }
        }
    }

    // CP-39, RNF:0003: todas las pantallas de cada rol, con datos, a 360 px
    public function test_cp39_a_360_px_no_hay_desplazamiento_horizontal()
    {
        $publicante = Particular::factory()->create();
        $solicitante = Particular::factory()->create();
        $refugio = Refugio::factory()->validado()->create(['Nombre_Refugio' => 'Refugio de prueba del Prado']);
        RefugioDatoContacto::create(['ID_Usuario' => $refugio->ID_Usuario, 'Dato_Contacto' => '099000000']);
        $pendiente = Refugio::factory()->create(['Nombre_Refugio' => 'Refugio de prueba pendiente']);
        $admin = Administrador::factory()->create();

        $publicacion = $this->crearAdopcion($publicante->ID_Usuario);
        $otra = $this->crearAdopcion($refugio->ID_Usuario);

        $solicitud = new SolicitudAdopcion(['ID_Publicacion' => $publicacion->ID_Publicacion]);
        $solicitud->ID_Usuario = $solicitante->ID_Usuario;
        $solicitud->save();

        $reporte = new Reporte(['ID_Publicacion_Reportada' => $otra->ID_Publicacion, 'Motivo' => 'Publicación duplicada o spam']);
        $reporte->ID_Usuario_Reportante = $solicitante->ID_Usuario;
        $reporte->save();

        $this->browse(function (Browser $browser) use ($publicante, $solicitante, $refugio, $pendiente, $admin, $publicacion, $otra)
        {
            $browser->resize(360, 780);
            $desbordadas = [];
            $p = $publicacion->ID_Publicacion;

            // Invitado
            $browser->visit('/');
            $browser->driver->manage()->deleteAllCookies();
            $this->revisarPaginas($browser, ['/', '/nosotros', '/adopcion', '/perdidas', '/encontradas', '/refugios',
                '/refugios/' . $refugio->ID_Usuario, '/publicaciones/' . $p, '/ingresar', '/registro',
                '/registro-refugio', '/confirmar-correo'], $desbordadas);

            // Particular que publicó: sus publicaciones y las solicitudes que recibió
            $this->ingresar($browser, $publicante->usuario->Email);
            $this->revisarPaginas($browser, ['/cuenta', '/perfil', '/publicar', '/publicaciones/' . $p . '/editar',
                '/publicaciones/' . $p . '/solicitudes', '/solicitudes/recibidas', '/seguimientos'], $desbordadas);

            // Particular que solicita y reporta
            $this->ingresar($browser, $solicitante->usuario->Email);
            $this->revisarPaginas($browser, ['/solicitudes', '/publicaciones/' . $otra->ID_Publicacion . '/solicitar',
                '/reportar?publicacion=' . $p], $desbordadas);

            // Refugio
            $this->ingresar($browser, $refugio->usuario->Email);
            $this->revisarPaginas($browser, ['/cuenta', '/mi-refugio', '/perfil'], $desbordadas);

            // Administrador
            $this->ingresar($browser, $admin->usuario->Email, '/admin');
            $this->revisarPaginas($browser, ['/admin', '/admin/refugios', '/admin/refugios/' . $pendiente->ID_Usuario . '/editar',
                '/admin/particulares', '/admin/particulares/' . $publicante->ID_Usuario . '/editar',
                '/admin/publicaciones', '/admin/reportes'], $desbordadas);

            $this->assertEmpty($desbordadas, 'Desplazamiento horizontal en: ' . implode(', ', $desbordadas));
        });
    }

    public function test_en_el_celular_el_menu_se_abre_y_muestra_los_enlaces()
    {
        $this->browse(function (Browser $browser)
        {
            // Sin sesión: la sesión está en archivos y podía quedar abierta de la prueba anterior
            $browser->visit('/');
            $browser->driver->manage()->deleteAllCookies();

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
