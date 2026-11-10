<?php

namespace Tests\Feature;

use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vigencia y cierre de mascotas perdidas y encontradas (RF:0047, RNE17). Caso CP-14.
 * travelTo() simula la fecha para probar los días 29 y 30 sin esperar.
 */
class VigenciaTest extends TestCase
{
    use RefreshDatabase;

    private $particular;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(10, 0));
        $this->particular = Particular::factory()->create();
    }

    private function sesion()
    {
        return ['id_usuario' => $this->particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'];
    }

    // Crea una perdida o encontrada Vigente del particular, publicada hoy (vence en 30 días)
    private function crearAviso($tipo = 'Perdida', $nombre = 'Luna')
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Gato', 'Raza' => 'mestizo', 'Sexo' => 'Hembra']);
        $publicacion->ID_Usuario = $this->particular->ID_Usuario;
        $publicacion->Tipo_Publicacion = 'Perdida';
        $publicacion->save();

        $perdida = new PublicacionPerdida([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Tipo_Aviso' => $tipo,
            'Nombre_Mascota' => $nombre,
            'Caracteristicas' => 'Collar rojo',
            'Fecha_Suceso' => now()->subDay()->toDateString(),
            'Zona_Suceso' => 'Malvín',
            'Contacto' => '098765432',
        ]);
        $perdida->Fecha_Vencimiento = now()->addDays(30)->toDateString();
        $perdida->save();

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/prueba.jpg']);

        return $perdida;
    }

    // ---------- Vencimiento automático (tarea de las 03:00) ----------

    public function test_cp14_el_dia_29_sigue_vigente_y_se_ve_en_el_inicio()
    {
        $perdida = $this->crearAviso();

        $this->travelTo(now()->addDays(29)->setTime(3, 0));
        $this->artisan('publicaciones:archivar-vencidas');

        $this->assertEquals('Vigente', $perdida->fresh()->Estado_Perdida);
        $this->get('/')->assertSee('Luna');
    }

    public function test_cp14_el_dia_30_pasa_a_archivada_y_sale_del_inicio()
    {
        $perdida = $this->crearAviso();

        $this->travelTo(now()->addDays(30)->setTime(3, 0));
        $this->artisan('publicaciones:archivar-vencidas');

        $this->assertEquals('Archivada', $perdida->fresh()->Estado_Perdida);
        $this->get('/')->assertDontSee('Luna');
    }

    public function test_la_tarea_tambien_archiva_las_encontradas_y_no_toca_las_resueltas()
    {
        $encontrada = $this->crearAviso('Encontrada', null);
        $resuelta = $this->crearAviso('Perdida', 'Toby');
        $resuelta->Estado_Perdida = 'Resuelta';
        $resuelta->save();

        $this->travelTo(now()->addDays(30));
        $this->artisan('publicaciones:archivar-vencidas');

        $this->assertEquals('Archivada', $encontrada->fresh()->Estado_Perdida);
        $this->assertEquals('Resuelta', $resuelta->fresh()->Estado_Perdida);
    }

    public function test_la_tarea_esta_programada_a_las_3()
    {
        $this->artisan('schedule:list')->expectsOutputToContain('publicaciones:archivar-vencidas');
    }

    // ---------- Renovar ----------

    public function test_cp14_renovar_una_vigente_antes_de_vencer_suma_30_dias_desde_hoy()
    {
        $perdida = $this->crearAviso();
        $this->travelTo(now()->addDays(20));

        $this->withSession($this->sesion())->put('/publicaciones/' . $perdida->ID_Publicacion . '/renovar');

        $perdida = $perdida->fresh();
        $this->assertEquals('Vigente', $perdida->Estado_Perdida);
        $this->assertEquals(now()->addDays(30)->toDateString(), $perdida->Fecha_Vencimiento);
    }

    public function test_cp14_renovar_una_archivada_la_vuelve_vigente_y_visible()
    {
        $perdida = $this->crearAviso();
        $this->travelTo(now()->addDays(31));
        $this->artisan('publicaciones:archivar-vencidas');
        $this->get('/')->assertDontSee('Luna');

        $this->withSession($this->sesion())->put('/publicaciones/' . $perdida->ID_Publicacion . '/renovar');

        $perdida = $perdida->fresh();
        $this->assertEquals('Vigente', $perdida->Estado_Perdida);
        $this->assertEquals(now()->addDays(30)->toDateString(), $perdida->Fecha_Vencimiento);
        $this->flushSession();
        $this->get('/')->assertSee('Luna');
    }

    // ---------- Resolver y archivar ----------

    public function test_cp14_resuelta_sale_del_inicio_y_ya_no_se_puede_renovar()
    {
        $perdida = $this->crearAviso();

        $this->withSession($this->sesion())->put('/publicaciones/' . $perdida->ID_Publicacion . '/resolver');
        $this->assertEquals('Resuelta', $perdida->fresh()->Estado_Perdida);

        $this->withSession($this->sesion())
            ->put('/publicaciones/' . $perdida->ID_Publicacion . '/renovar')
            ->assertSessionHas('error');
        $this->assertEquals('Resuelta', $perdida->fresh()->Estado_Perdida);

        $this->flushSession();
        $this->get('/')->assertDontSee('Luna');
    }

    public function test_archivar_la_saca_del_inicio_y_no_se_puede_resolver_archivada()
    {
        $perdida = $this->crearAviso();

        $this->withSession($this->sesion())->put('/publicaciones/' . $perdida->ID_Publicacion . '/archivar');
        $this->assertEquals('Archivada', $perdida->fresh()->Estado_Perdida);

        $this->withSession($this->sesion())
            ->put('/publicaciones/' . $perdida->ID_Publicacion . '/resolver')
            ->assertSessionHas('error');
        $this->assertEquals('Archivada', $perdida->fresh()->Estado_Perdida);

        $this->flushSession();
        $this->get('/')->assertDontSee('Luna');
    }

    // ---------- Acceso ----------

    public function test_rne03_otro_usuario_no_puede_cambiar_la_publicacion()
    {
        $perdida = $this->crearAviso();
        $otro = Particular::factory()->create();
        $sesionOtro = ['id_usuario' => $otro->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Otro'];

        foreach (['renovar', 'resolver', 'archivar'] as $accion)
        {
            $this->withSession($sesionOtro)->put('/publicaciones/' . $perdida->ID_Publicacion . '/' . $accion)->assertForbidden();
        }

        $this->assertEquals('Vigente', $perdida->fresh()->Estado_Perdida);
    }

    public function test_sin_sesion_se_pide_ingresar()
    {
        $perdida = $this->crearAviso();

        $this->put('/publicaciones/' . $perdida->ID_Publicacion . '/archivar')->assertRedirect('/ingresar');
        $this->assertEquals('Vigente', $perdida->fresh()->Estado_Perdida);
    }

    public function test_mi_cuenta_ofrece_solo_las_acciones_validas_segun_el_estado()
    {
        $vigente = $this->crearAviso('Perdida', 'Luna');
        $archivada = $this->crearAviso('Perdida', 'Toby');
        $archivada->Estado_Perdida = 'Archivada';
        $archivada->save();

        $pagina = $this->withSession($this->sesion())->get('/cuenta');

        $pagina->assertSee('/publicaciones/' . $vigente->ID_Publicacion . '/resolver', false);
        $pagina->assertSee('/publicaciones/' . $archivada->ID_Publicacion . '/renovar', false);
        $pagina->assertDontSee('/publicaciones/' . $archivada->ID_Publicacion . '/resolver', false);
    }
}
