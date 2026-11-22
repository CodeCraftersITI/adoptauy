<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\Refugio;
use App\Models\Reporte;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Reportes y moderación (RF:0059, RF:0061, RF:0060)
class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private $publicante;
    private $publicacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicante = Particular::factory()->create();
        $this->publicacion = $this->crearAdopcion($this->publicante->ID_Usuario, 'Caniche');
    }

    private function crearAdopcion($idUsuario, $raza)
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Perro', 'Raza' => $raza, 'Sexo' => 'Macho']);
        $publicacion->ID_Usuario = $idUsuario;
        $publicacion->Tipo_Publicacion = 'Adopción';
        $publicacion->save();

        PublicacionAdopcion::create([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Edad_Meses' => 24,
            'Tamaño' => 'Mediano',
            'Estado_Salud' => 'Sano',
            'Estado_Esterilizacion' => 'Esterilizado',
            'Ubicacion_Aproximada' => 'Pocitos',
        ]);

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/uno.jpg']);

        return $publicacion;
    }

    private function sesion($particular)
    {
        return ['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'];
    }

    private function sesionAdmin()
    {
        $admin = Administrador::factory()->create();

        return ['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'];
    }

    // N particulares distintos reportan lo mismo
    private function reportar($datos, $cantidad)
    {
        for ($i = 0; $i < $cantidad; $i++)
        {
            $this->withSession($this->sesion(Particular::factory()->create()))->post('/reportar', $datos);
        }
    }

    private function datosPublicacion($motivo = 'Publicación duplicada o spam')
    {
        return ['publicacion' => $this->publicacion->ID_Publicacion, 'motivo' => $motivo];
    }

    // ---------- CP-30: reportar ----------

    public function test_cp30_el_reporte_queda_pendiente_con_el_motivo_elegido()
    {
        $reportante = Particular::factory()->create();

        $this->withSession($this->sesion($reportante))->get('/reportar?publicacion=' . $this->publicacion->ID_Publicacion)
            ->assertOk()
            ->assertSee('Venta de animales');

        $this->withSession($this->sesion($reportante))->post('/reportar', $this->datosPublicacion('Venta de animales'))
            ->assertRedirect('/publicaciones/' . $this->publicacion->ID_Publicacion)
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('Reporte', [
            'ID_Usuario_Reportante' => $reportante->ID_Usuario,
            'ID_Publicacion_Reportada' => $this->publicacion->ID_Publicacion,
            'Motivo' => 'Venta de animales',
            'Estado_Reporte' => 'Pendiente',
        ]);
    }

    public function test_cp30_el_mismo_usuario_no_reporta_dos_veces_lo_mismo()
    {
        $sesion = $this->sesion(Particular::factory()->create());

        $this->withSession($sesion)->post('/reportar', $this->datosPublicacion());
        $this->withSession($sesion)->post('/reportar', $this->datosPublicacion('Venta de animales'))
            ->assertSessionHas('error', 'Ya enviaste un reporte sobre esto.');

        $this->assertDatabaseCount('Reporte', 1);
    }

    public function test_cp30_sin_sesion_no_se_puede_reportar()
    {
        $this->get('/reportar?publicacion=' . $this->publicacion->ID_Publicacion)->assertRedirect('/ingresar');
        $this->post('/reportar', $this->datosPublicacion())->assertRedirect('/ingresar');

        $this->assertDatabaseCount('Reporte', 0);
    }

    public function test_rne22_nadie_se_reporta_a_si_mismo_ni_a_su_publicacion()
    {
        $sesion = $this->sesion($this->publicante);

        $this->withSession($sesion)->post('/reportar', $this->datosPublicacion())->assertSessionHas('error');
        $this->withSession($sesion)->post('/reportar', ['usuario' => $this->publicante->ID_Usuario, 'motivo' => 'Cuenta falsa o duplicada'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('Reporte', 0);
    }

    public function test_cp30_el_motivo_tiene_que_ser_de_la_lista_y_otro_pide_el_detalle()
    {
        $sesion = $this->sesion(Particular::factory()->create());

        // Motivo de otro tipo de objeto (es de refugios)
        $this->withSession($sesion)->post('/reportar', $this->datosPublicacion('Suplantación de otro refugio'))->assertSessionHasErrors('motivo');
        $this->withSession($sesion)->post('/reportar', $this->datosPublicacion('Otro'))->assertSessionHasErrors('detalle');

        $this->withSession($sesion)->post('/reportar', $this->datosPublicacion('Otro') + ['detalle' => 'Pide dinero por el perro'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('Reporte', ['Motivo' => 'Otro', 'Detalle' => 'Pide dinero por el perro']);
    }

    public function test_no_se_puede_reportar_al_administrador()
    {
        $admin = Administrador::factory()->create();

        $this->withSession($this->sesion(Particular::factory()->create()))
            ->get('/reportar?usuario=' . $admin->ID_Usuario)
            ->assertNotFound();
    }

    // ---------- CP-31: umbrales ----------

    public function test_cp31_publicacion_con_2_reportes_sigue_visible_y_con_3_se_oculta()
    {
        $this->reportar($this->datosPublicacion(), 2);
        $this->assertEquals('Visible', $this->publicacion->fresh()->Estado_Moderacion);
        $this->get('/adopcion')->assertSee('Caniche');

        $this->reportar($this->datosPublicacion(), 1);
        $this->assertEquals('En revisión', $this->publicacion->fresh()->Estado_Moderacion);
        $this->get('/adopcion')->assertDontSee('Caniche');
        $this->get('/publicaciones/' . $this->publicacion->ID_Publicacion)->assertNotFound();
    }

    public function test_cp31_particular_en_revision_con_5_e_inhabilitado_con_10()
    {
        $datos = ['usuario' => $this->publicante->ID_Usuario, 'motivo' => 'Usuario malicioso o fraudulento'];

        $this->reportar($datos, 4);
        $this->assertEquals('Activo', $this->publicante->usuario->fresh()->Estado_Usuario);

        $this->reportar($datos, 1);
        $this->assertEquals('En revisión', $this->publicante->usuario->fresh()->Estado_Usuario);

        $this->reportar($datos, 4);
        $this->assertEquals('En revisión', $this->publicante->usuario->fresh()->Estado_Usuario);

        $this->reportar($datos, 1);
        $this->assertEquals('Inhabilitado', $this->publicante->usuario->fresh()->Estado_Usuario);

        // Inhabilitado no puede entrar
        $this->flushSession();
        $this->post('/ingresar', ['email' => $this->publicante->usuario->Email, 'password' => UsuarioFactory::CONTRASEÑA])
            ->assertSessionHas('error');
    }

    public function test_cp31_refugio_suspendido_con_3_reportes()
    {
        $refugio = Refugio::factory()->validado()->create();
        $datos = ['usuario' => $refugio->ID_Usuario, 'motivo' => 'Datos de ubicación o contacto falsos'];

        $this->reportar($datos, 2);
        $this->assertEquals('Validado', $refugio->fresh()->Estado_Validacion);

        $this->reportar($datos, 1);
        $this->assertEquals('Suspendido', $refugio->fresh()->Estado_Validacion);
        $this->get('/refugios/' . $refugio->ID_Usuario)->assertNotFound();
    }

    public function test_cp31_los_reportes_desestimados_no_cuentan()
    {
        $this->reportar($this->datosPublicacion(), 2);
        Reporte::query()->update(['Estado_Reporte' => 'Desestimado']);

        $this->reportar($this->datosPublicacion(), 2);
        $this->assertEquals('Visible', $this->publicacion->fresh()->Estado_Moderacion);
    }

    // ---------- CP-32: resolución ----------

    public function test_cp32_desestimar_devuelve_la_visibilidad_y_registra_la_fecha()
    {
        $this->reportar($this->datosPublicacion(), 3);

        $this->withSession($this->sesionAdmin())->get('/admin/reportes')->assertOk()->assertSee('3 reportes');

        $this->withSession($this->sesionAdmin())
            ->put('/admin/reportes/publicacion/' . $this->publicacion->ID_Publicacion, ['medida' => 'desestimar']);

        $this->assertEquals('Visible', $this->publicacion->fresh()->Estado_Moderacion);
        $this->assertEquals(3, Reporte::where('Estado_Reporte', 'Desestimado')->whereNotNull('Fecha_Resolucion')->count());
    }

    public function test_cp32_confirmar_suspende_la_publicacion_y_registra_la_medida()
    {
        $this->reportar($this->datosPublicacion(), 1);

        $this->withSession($this->sesionAdmin())
            ->put('/admin/reportes/publicacion/' . $this->publicacion->ID_Publicacion, ['medida' => 'suspension']);

        $this->assertEquals('Suspendida', $this->publicacion->fresh()->Estado_Moderacion);
        $this->assertDatabaseHas('Reporte', ['Estado_Reporte' => 'Confirmado', 'Medida' => 'Suspensión']);
        $this->assertNotNull(Reporte::first()->Fecha_Resolucion);
    }

    public function test_cp31_tres_publicaciones_suspendidas_mandan_al_particular_a_revision()
    {
        $publicaciones = [$this->publicacion, $this->crearAdopcion($this->publicante->ID_Usuario, 'Beagle'), $this->crearAdopcion($this->publicante->ID_Usuario, 'Pug')];
        $admin = $this->sesionAdmin();

        foreach ($publicaciones as $numero => $publicacion)
        {
            $this->reportar(['publicacion' => $publicacion->ID_Publicacion, 'motivo' => 'Venta de animales'], 1);
            $this->withSession($admin)->put('/admin/reportes/publicacion/' . $publicacion->ID_Publicacion, ['medida' => 'suspension']);

            $esperado = $numero < 2 ? 'Activo' : 'En revisión';
            $this->assertEquals($esperado, $this->publicante->usuario->fresh()->Estado_Usuario);
        }
    }

    public function test_cp32_suspender_y_dar_de_baja_a_un_particular_y_restituirlo()
    {
        $this->reportar(['usuario' => $this->publicante->ID_Usuario, 'motivo' => 'Suplantación de identidad'], 1);
        $admin = $this->sesionAdmin();

        $this->withSession($admin)->put('/admin/reportes/usuario/' . $this->publicante->ID_Usuario, ['medida' => 'suspension']);
        $this->assertEquals('Suspendido', $this->publicante->usuario->fresh()->Estado_Usuario);
        $this->assertDatabaseHas('Reporte', ['Medida' => 'Suspensión', 'Estado_Reporte' => 'Confirmado']);

        // Suspendido no entra
        $this->flushSession();
        $this->post('/ingresar', ['email' => $this->publicante->usuario->Email, 'password' => UsuarioFactory::CONTRASEÑA])->assertSessionHas('error');

        $this->withSession($admin)->put('/admin/particulares/' . $this->publicante->ID_Usuario . '/restituir');
        $this->assertEquals('Activo', $this->publicante->usuario->fresh()->Estado_Usuario);
    }

    public function test_cp32_baja_del_refugio_por_reportes_archiva_sus_publicaciones()
    {
        $refugio = Refugio::factory()->validado()->create();
        $publicacion = $this->crearAdopcion($refugio->ID_Usuario, 'Labrador');
        $this->reportar(['usuario' => $refugio->ID_Usuario, 'motivo' => 'Refugio no legítimo (documentación falsa)'], 1);

        $this->withSession($this->sesionAdmin())->put('/admin/reportes/usuario/' . $refugio->ID_Usuario, ['medida' => 'baja']);

        $this->assertEquals('Dado de baja', $refugio->fresh()->Estado_Validacion);
        $this->assertEquals('Archivado', $publicacion->adopcion->fresh()->Estado_Adopcion);
        $this->assertDatabaseHas('Reporte', ['Medida' => 'Baja lógica']);
    }

    public function test_el_aviso_solo_queda_registrado()
    {
        $this->reportar(['usuario' => $this->publicante->ID_Usuario, 'motivo' => 'Acoso o mensajes abusivos'], 1);

        $this->withSession($this->sesionAdmin())->put('/admin/reportes/usuario/' . $this->publicante->ID_Usuario, ['medida' => 'aviso']);

        $this->assertDatabaseHas('Reporte', ['Medida' => 'Aviso', 'Estado_Reporte' => 'Confirmado']);
        $this->assertEquals('Activo', $this->publicante->usuario->fresh()->Estado_Usuario);
    }

    public function test_solo_el_administrador_resuelve_reportes()
    {
        $sesion = $this->sesion(Particular::factory()->create());

        $this->withSession($sesion)->get('/admin/reportes')->assertForbidden();
        $this->withSession($sesion)->put('/admin/reportes/publicacion/' . $this->publicacion->ID_Publicacion, ['medida' => 'desestimar'])->assertForbidden();
    }
}
