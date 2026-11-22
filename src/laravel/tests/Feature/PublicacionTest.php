<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Particular;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Alta de publicaciones (RF:0017, RF:0024, RF:0046, RF:0064)
class PublicacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // Sesión de un Particular real (la publicación necesita un ID_Usuario que exista)
    private function sesionParticular()
    {
        $particular = Particular::factory()->create();

        return ['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'];
    }

    private function fotos($cantidad)
    {
        $fotos = [];
        for ($i = 1; $i <= $cantidad; $i++)
        {
            $fotos[] = UploadedFile::fake()->image('foto' . $i . '.jpg');
        }

        return $fotos;
    }

    // Datos de CP-06 (sin temperamento: el campo se eliminó del alcance)
    private function datosAdopcion($cambios = [])
    {
        return array_merge([
            'tipo' => 'Adopción',
            'especie' => 'Perro',
            'raza' => 'mestizo',
            'sexo' => 'Macho',
            'edad' => 8,
            'edad_unidad' => 'meses',
            'tamanio' => 'Mediano',
            'salud' => 'Sano, vacunas al día',
            'esterilizacion' => 'Esterilizado',
            'ubicacion' => 'Pocitos',
            'fotos' => $this->fotos(3),
        ], $cambios);
    }

    // Datos de CP-09
    private function datosPerdida($cambios = [])
    {
        return array_merge([
            'tipo' => 'Perdida',
            'nombre_mascota' => 'Luna',
            'especie' => 'Gato',
            'raza' => 'siamesa',
            'sexo' => 'Hembra',
            'caracteristicas' => 'Collar rojo',
            'fecha' => now()->subDays(2)->toDateString(),
            'zona' => 'Malvín',
            'contacto' => '098 765 432',
            'fotos' => $this->fotos(2),
        ], $cambios);
    }

    // ---------- Adopción ----------

    // Edad con número y unidad (corrección del docente): se guarda siempre en meses (RNE12)
    public function test_la_edad_en_meses_o_en_anios_se_guarda_en_meses()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['edad' => 4, 'edad_unidad' => 'meses']))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['edad' => 2, 'edad_unidad' => 'años']))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('Publicacion_Adopcion', ['Edad_Meses' => 4]);
        $this->assertDatabaseHas('Publicacion_Adopcion', ['Edad_Meses' => 24]);
    }

    public function test_sin_tipo_o_con_un_tipo_inventado_no_se_publica()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['tipo' => '']))->assertSessionHasErrors('tipo');
        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['tipo' => 'Venta']))->assertSessionHasErrors('tipo');
        $this->assertDatabaseCount('Publicacion_Animal', 0);
    }

    public function test_el_formulario_de_adopcion_se_muestra()
    {
        $this->withSession($this->sesionParticular())->get('/publicar')->assertOk()->assertSee('Publicar mascota')->assertSee('En adopción');
    }

    public function test_cp06_alta_valida_queda_en_adopcion_y_aparece_en_el_inicio()
    {
        $this->withSession($this->sesionParticular())
            ->post('/publicar', $this->datosAdopcion())
            ->assertRedirect('/cuenta');

        $publicacion = PublicacionAnimal::first();
        $this->assertEquals('Adopción', $publicacion->Tipo_Publicacion);
        $this->assertEquals('En adopción', $publicacion->adopcion->Estado_Adopcion);
        $this->assertEquals(8, $publicacion->adopcion->Edad_Meses);
        $this->assertCount(3, $publicacion->fotos);
        Storage::disk('public')->assertExists($publicacion->fotos->first()->URL_Foto);

        // Visible en el listado público, sin sesión
        $this->flushSession();
        $this->get('/')->assertSee('Pocitos')->assertSee('8 meses');
    }

    public function test_cp07_acepta_1_y_6_fotos()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['fotos' => $this->fotos(1)]))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['fotos' => $this->fotos(6)]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('Publicacion_Animal', 2);
        $this->assertDatabaseCount('Foto_Animal', 7);
    }

    public function test_cp07_rechaza_0_y_7_fotos()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['fotos' => []]))->assertSessionHasErrors('fotos');
        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['fotos' => $this->fotos(7)]))->assertSessionHasErrors('fotos');

        $this->assertDatabaseCount('Publicacion_Animal', 0);
    }

    public function test_cp07_rechaza_sin_especie_un_exe_renombrado_y_una_foto_de_9_mb()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['especie' => '']))->assertSessionHasErrors('especie');

        $exe = UploadedFile::fake()->create('virus.jpg', 10, 'application/x-msdownload');
        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['fotos' => [$exe]]))->assertSessionHasErrors('fotos.0');

        $grande = UploadedFile::fake()->create('grande.jpg', 9 * 1024, 'image/jpeg');
        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion(['fotos' => [$grande]]))->assertSessionHasErrors('fotos.0');

        $this->assertDatabaseCount('Publicacion_Animal', 0);
    }

    public function test_un_refugio_validado_tambien_publica_en_adopcion()
    {
        $refugio = Refugio::factory()->validado()->create();
        $sesion = ['id_usuario' => $refugio->ID_Usuario, 'tipo' => 'Refugio', 'nombre' => $refugio->Nombre_Refugio];

        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion())->assertSessionHasNoErrors();

        $this->assertEquals($refugio->ID_Usuario, PublicacionAnimal::first()->ID_Usuario);
    }

    // ---------- Perdida ----------

    public function test_cp09_alta_valida_queda_vigente_con_vencimiento_a_30_dias_y_es_publica()
    {
        $this->withSession($this->sesionParticular())
            ->post('/publicar', $this->datosPerdida())
            ->assertRedirect('/cuenta');

        $perdida = PublicacionPerdida::first();
        $this->assertEquals('Perdida', $perdida->Tipo_Aviso);
        $this->assertEquals('Vigente', $perdida->Estado_Perdida);
        $this->assertEquals(now()->addDays(30)->toDateString(), $perdida->Fecha_Vencimiento);
        $this->assertCount(2, $perdida->publicacion->fotos);

        $this->flushSession();
        $this->get('/')->assertSee('Luna')->assertSee('098 765 432');
    }

    public function test_cp10_acepta_1_y_3_fotos_y_rechaza_0_y_4()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->datosPerdida(['fotos' => $this->fotos(1)]))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar', $this->datosPerdida(['fotos' => $this->fotos(3)]))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar', $this->datosPerdida(['fotos' => []]))->assertSessionHasErrors('fotos');
        $this->withSession($sesion)->post('/publicar', $this->datosPerdida(['fotos' => $this->fotos(4)]))->assertSessionHasErrors('fotos');

        $this->assertDatabaseCount('Publicacion_Perdida', 2);
    }

    public function test_cp10_rechaza_sin_zona_y_con_fecha_futura()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->datosPerdida(['zona' => '']))->assertSessionHasErrors('zona');
        $this->withSession($sesion)
            ->post('/publicar', $this->datosPerdida(['fecha' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('fecha');

        $this->assertDatabaseCount('Publicacion_Animal', 0);
    }

    // ---------- Acceso ----------

    public function test_cp11_sin_sesion_los_formularios_y_los_post_redirigen_a_ingresar()
    {
        $this->get('/publicar')->assertRedirect('/ingresar');
        $this->post('/publicar', $this->datosAdopcion())->assertRedirect('/ingresar');
        $this->post('/publicar', $this->datosPerdida())->assertRedirect('/ingresar');

        $this->assertDatabaseCount('Publicacion_Animal', 0);
    }

    public function test_el_administrador_no_publica()
    {
        $admin = Administrador::factory()->create();
        $sesion = ['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'];

        $this->withSession($sesion)->post('/publicar', $this->datosAdopcion())->assertForbidden();
        $this->assertDatabaseCount('Publicacion_Animal', 0);
    }

    public function test_mi_cuenta_muestra_mis_publicaciones_con_su_estado()
    {
        $sesion = $this->sesionParticular();
        $this->withSession($sesion)->post('/publicar', $this->datosPerdida());

        $this->withSession($sesion)->get('/cuenta')
            ->assertSee('Luna')
            ->assertSee('Perdida');
    }
}
