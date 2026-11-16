<?php

namespace Tests\Feature;

use App\Mail\AvisoSeguimiento;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\Seguimiento;
use App\Models\SolicitudAdopcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Seguimiento posterior a la adopción: aviso del día 15 (RF:0055, CP-27), envío y revisión
 * de la foto (RF:0056, RF:0057, CP-28) y restricción del día 22 (RF:0058, CP-29).
 * travelTo() simula la fecha: la adopción se concreta el 01/11/2026, así que
 * el día 15 es el 16/11 y el día 22 es el 23/11.
 */
class SeguimientoTest extends TestCase
{
    use RefreshDatabase;

    private $publicante;
    private $adoptante;
    private $seguimiento;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Storage::fake('public');
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(10, 0));

        $this->publicante = Particular::factory()->create();
        $this->adoptante = Particular::factory()->create();

        $publicacion = $this->crearAdopcion($this->publicante->ID_Usuario);

        $solicitud = new SolicitudAdopcion(['ID_Publicacion' => $publicacion->ID_Publicacion]);
        $solicitud->ID_Usuario = $this->adoptante->ID_Usuario;
        $solicitud->save();

        // El publicante concreta la adopción el día 0: se crean la adopción y su seguimiento
        $this->withSession($this->sesion($this->publicante))->put('/solicitudes/' . $solicitud->ID_Solicitud . '/elegir');
        $this->flushSession();

        $this->seguimiento = Seguimiento::first();
    }

    private function crearAdopcion($idUsuario)
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Gato', 'Raza' => 'Persa', 'Sexo' => 'Hembra']);
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
        return ['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => $particular->Nombre];
    }

    // Lleva la fecha simulada al día N desde la adopción, a las 03:00 (hora de la tarea)
    private function irAlDia($dia)
    {
        $this->travelTo(now()->setDate(2026, 11, 1)->addDays($dia)->setTime(3, 0));
    }

    private function enviarFoto()
    {
        return $this->withSession($this->sesion($this->adoptante))
            ->post('/seguimientos/' . $this->seguimiento->ID_Adopcion . '/foto', ['foto' => UploadedFile::fake()->image('gata.jpg')]);
    }

    private function puedePublicar()
    {
        return $this->withSession($this->sesion($this->adoptante))->get('/publicar')->status() == 200;
    }

    // ---------- Al concretar la adopción (RNE20) ----------

    public function test_rne20_al_concretar_se_crea_el_seguimiento_con_los_dias_15_y_22()
    {
        $this->assertEquals('2026-11-16', $this->seguimiento->Fecha_Aviso);
        $this->assertEquals('2026-11-23', $this->seguimiento->Fecha_Limite);
        $this->assertEquals('Pendiente', $this->seguimiento->Estado_Evidencia);
    }

    // ---------- CP-27: aviso del día 15 ----------

    public function test_cp27_el_dia_14_no_hay_aviso_el_15_llega_uno_y_el_16_no_se_repite()
    {
        $this->irAlDia(14);
        $this->artisan('seguimientos:avisar');
        Mail::assertNotSent(AvisoSeguimiento::class);

        $this->irAlDia(15);
        $this->artisan('seguimientos:avisar');
        Mail::assertSent(AvisoSeguimiento::class, 1);
        Mail::assertSent(AvisoSeguimiento::class, function ($correo)
        {
            return $correo->hasTo($this->adoptante->usuario->Email);
        });

        $this->irAlDia(16);
        $this->artisan('seguimientos:avisar');
        Mail::assertSent(AvisoSeguimiento::class, 1);
    }

    // ---------- CP-28: envío y revisión ----------

    public function test_cp28_antes_del_dia_15_no_se_puede_enviar_la_foto()
    {
        $this->irAlDia(14);
        $this->enviarFoto()->assertSessionHas('error');

        $this->assertEquals('Pendiente', $this->seguimiento->fresh()->Estado_Evidencia);
    }

    public function test_cp28_la_foto_queda_enviada_y_la_ve_el_publicante()
    {
        $this->irAlDia(15);
        $this->enviarFoto()->assertSessionHas('exito');

        $seguimiento = $this->seguimiento->fresh();
        $this->assertEquals('Enviada', $seguimiento->Estado_Evidencia);
        Storage::disk('public')->assertExists($seguimiento->URL_Foto);

        $this->withSession($this->sesion($this->publicante))->get('/seguimientos')
            ->assertSee($seguimiento->URL_Foto)
            ->assertSee('Aprobar');
    }

    public function test_cp28_si_la_rechaza_se_pide_otra_y_al_reenviarla_vuelve_a_enviada()
    {
        $this->irAlDia(16);
        $this->enviarFoto();

        $this->withSession($this->sesion($this->publicante))->put('/seguimientos/' . $this->seguimiento->ID_Adopcion . '/rechazar');
        $this->assertEquals('Rechazada', $this->seguimiento->fresh()->Estado_Evidencia);

        $this->withSession($this->sesion($this->adoptante))->get('/seguimientos')->assertSee('enviá otra');

        $this->enviarFoto();
        $this->assertEquals('Enviada', $this->seguimiento->fresh()->Estado_Evidencia);
    }

    public function test_cp28_sin_revision_la_foto_cuenta_como_presentada()
    {
        $this->irAlDia(16);
        $this->enviarFoto();

        // Pasa el día 22 y el publicante nunca la revisó: no hay restricción (D72)
        $this->irAlDia(30);
        $this->assertTrue($this->puedePublicar());
    }

    public function test_cp28_solo_el_adoptante_envia_y_solo_el_publicante_revisa()
    {
        $this->irAlDia(15);
        $otro = Particular::factory()->create();

        $this->withSession($this->sesion($otro))
            ->post('/seguimientos/' . $this->seguimiento->ID_Adopcion . '/foto', ['foto' => UploadedFile::fake()->image('x.jpg')])
            ->assertForbidden();

        $this->enviarFoto();

        $this->withSession($this->sesion($this->adoptante))->put('/seguimientos/' . $this->seguimiento->ID_Adopcion . '/aprobar')->assertForbidden();
        $this->withSession($this->sesion($otro))->put('/seguimientos/' . $this->seguimiento->ID_Adopcion . '/aprobar')->assertForbidden();

        $this->withSession($this->sesion($this->publicante))->put('/seguimientos/' . $this->seguimiento->ID_Adopcion . '/aprobar');
        $this->assertEquals('Aprobada', $this->seguimiento->fresh()->Estado_Evidencia);
    }

    public function test_cp28_la_foto_tiene_que_ser_una_imagen()
    {
        $this->irAlDia(15);

        $this->withSession($this->sesion($this->adoptante))
            ->post('/seguimientos/' . $this->seguimiento->ID_Adopcion . '/foto', ['foto' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('foto');

        $this->assertEquals('Pendiente', $this->seguimiento->fresh()->Estado_Evidencia);
    }

    // ---------- CP-29: restricción del día 22 ----------

    public function test_cp29_el_dia_21_no_hay_restriccion_y_el_22_no_puede_solicitar_ni_publicar()
    {
        $this->irAlDia(21);
        $this->assertTrue($this->puedePublicar());

        $this->irAlDia(22);
        $this->withSession($this->sesion($this->adoptante))->get('/publicar')->assertRedirect('/seguimientos');
        $this->withSession($this->sesion($this->adoptante))->post('/publicar', ['tipo' => 'Adopción'])->assertRedirect('/seguimientos');

        // Tampoco puede solicitar otra adopción
        $otra = $this->crearAdopcion($this->publicante->ID_Usuario);
        $this->withSession($this->sesion($this->adoptante))
            ->post('/publicaciones/' . $otra->ID_Publicacion . '/solicitar', ['acepto' => '1'])
            ->assertSessionHas('error', Seguimiento::MENSAJE_RESTRINGIDA);
        $this->assertDatabaseCount('Solicitud_Adopcion', 1);

        // Mi cuenta muestra el aviso
        $this->withSession($this->sesion($this->adoptante))->get('/cuenta')->assertSee('Tu cuenta está restringida');
    }

    public function test_cp29_con_la_cuenta_restringida_puede_enviar_la_foto_y_se_levanta_la_restriccion()
    {
        $this->irAlDia(25);
        $this->assertFalse($this->puedePublicar());

        $this->enviarFoto()->assertSessionHas('exito');

        $this->assertTrue($this->puedePublicar());
    }

    public function test_cp29_si_rechaza_la_foto_despues_del_dia_22_la_cuenta_se_restringe()
    {
        $this->irAlDia(20);
        $this->enviarFoto();

        $this->irAlDia(24);
        $this->assertTrue($this->puedePublicar());

        $this->withSession($this->sesion($this->publicante))->put('/seguimientos/' . $this->seguimiento->ID_Adopcion . '/rechazar');
        $this->assertFalse($this->puedePublicar());

        // Al enviar otra, se levanta
        $this->enviarFoto();
        $this->assertTrue($this->puedePublicar());
    }

    public function test_el_publicante_no_queda_restringido_por_la_adopcion_de_otro()
    {
        $this->irAlDia(30);

        $this->withSession($this->sesion($this->publicante))->get('/publicar')->assertOk();
    }
}
