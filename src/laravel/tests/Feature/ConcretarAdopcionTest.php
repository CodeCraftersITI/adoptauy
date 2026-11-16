<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\SolicitudAdopcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Responder solicitudes: rechazar una (RF:0051, CP-24) y concretar la adopción eligiendo
 * al adoptante (RF:0052, RNE19, CP-25). Transiciones de 13.2.3.
 */
class ConcretarAdopcionTest extends TestCase
{
    use RefreshDatabase;

    private $publicante;
    private $publicacion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 15)->setTime(10, 0));

        $this->publicante = Particular::factory()->create();

        $this->publicacion = new PublicacionAnimal(['Especie' => 'Perro', 'Raza' => 'Mestizo', 'Sexo' => 'Macho']);
        $this->publicacion->ID_Usuario = $this->publicante->ID_Usuario;
        $this->publicacion->Tipo_Publicacion = 'Adopción';
        $this->publicacion->save();

        PublicacionAdopcion::create([
            'ID_Publicacion' => $this->publicacion->ID_Publicacion,
            'Edad_Meses' => 8,
            'Tamaño' => 'Mediano',
            'Estado_Salud' => 'Sano',
            'Estado_Esterilizacion' => 'Esterilizado',
            'Ubicacion_Aproximada' => 'Pocitos',
        ]);

        FotoAnimal::create(['ID_Publicacion' => $this->publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/uno.jpg']);
    }

    // Una solicitud Pendiente de un particular nuevo sobre la mascota
    private function crearSolicitud()
    {
        $solicitante = Particular::factory()->create();

        $solicitud = new SolicitudAdopcion(['ID_Publicacion' => $this->publicacion->ID_Publicacion]);
        $solicitud->ID_Usuario = $solicitante->ID_Usuario;
        $solicitud->save();

        return $solicitud;
    }

    private function sesionPublicante()
    {
        return ['id_usuario' => $this->publicante->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'];
    }

    private function estadoMascota()
    {
        return PublicacionAdopcion::find($this->publicacion->ID_Publicacion)->Estado_Adopcion;
    }

    // ---------- Rechazar (CP-24) ----------

    public function test_cp24_rechazar_la_pasa_a_rechazada_y_la_publicacion_no_cambia()
    {
        $solicitud = $this->crearSolicitud();

        $this->withSession($this->sesionPublicante())->put('/solicitudes/' . $solicitud->ID_Solicitud . '/rechazar');

        $this->assertEquals('Rechazada', $solicitud->fresh()->Estado_Solicitud);
        $this->assertEquals('En adopción', $this->estadoMascota());

        // Deja de figurar entre las pendientes: ya no tiene los botones para responderla
        $this->withSession($this->sesionPublicante())->get('/solicitudes/recibidas')
            ->assertDontSee('Elegir como adoptante');
    }

    public function test_cp24_una_solicitud_rechazada_no_se_puede_volver_a_rechazar_ni_elegir()
    {
        $solicitud = $this->crearSolicitud();
        $solicitud->Estado_Solicitud = 'Rechazada';
        $solicitud->save();

        $this->withSession($this->sesionPublicante())->put('/solicitudes/' . $solicitud->ID_Solicitud . '/elegir')
            ->assertSessionHas('error');

        $this->assertEquals('Rechazada', $solicitud->fresh()->Estado_Solicitud);
        $this->assertEquals('En adopción', $this->estadoMascota());
    }

    // ---------- Concretar (CP-25) ----------

    public function test_cp25_con_tres_solicitudes_la_elegida_adoptada_las_otras_rechazadas_y_se_guarda_la_fecha()
    {
        $elegida = $this->crearSolicitud();
        $otra1 = $this->crearSolicitud();
        $otra2 = $this->crearSolicitud();

        $this->withSession($this->sesionPublicante())->put('/solicitudes/' . $elegida->ID_Solicitud . '/elegir')
            ->assertSessionHas('exito');

        $this->assertEquals('Adoptada', $elegida->fresh()->Estado_Solicitud);
        $this->assertEquals('Rechazada', $otra1->fresh()->Estado_Solicitud);
        $this->assertEquals('Rechazada', $otra2->fresh()->Estado_Solicitud);
        $this->assertEquals('Adoptado', $this->estadoMascota());

        $this->assertDatabaseHas('Adopcion', ['ID_Solicitud' => $elegida->ID_Solicitud, 'Fecha_Adopcion' => '2026-11-15']);

        // Ya no aparece en la consulta pública
        $this->get('/adopcion')->assertDontSee('Pocitos');
    }

    public function test_cp25_la_adopcion_es_definitiva()
    {
        $elegida = $this->crearSolicitud();
        $otra = $this->crearSolicitud();

        $this->withSession($this->sesionPublicante())->put('/solicitudes/' . $elegida->ID_Solicitud . '/elegir');

        // Ni elegir a otro, ni rechazar la elegida, ni el Administrador la devuelven a En adopción
        $this->withSession($this->sesionPublicante())->put('/solicitudes/' . $otra->ID_Solicitud . '/elegir')->assertSessionHas('error');
        $this->withSession($this->sesionPublicante())->put('/solicitudes/' . $elegida->ID_Solicitud . '/rechazar')->assertSessionHas('error');

        $admin = Administrador::factory()->create();
        $this->withSession(['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'])
            ->put('/admin/publicaciones/' . $this->publicacion->ID_Publicacion . '/archivar')
            ->assertSessionHas('error');

        $this->assertEquals('Adoptado', $this->estadoMascota());
        $this->assertEquals('Adoptada', $elegida->fresh()->Estado_Solicitud);
        $this->assertDatabaseCount('Adopcion', 1);
    }

    // ---------- Acceso ----------

    public function test_solo_el_publicante_puede_responder_la_solicitud()
    {
        $solicitud = $this->crearSolicitud();
        $otro = Particular::factory()->create();
        $sesionOtro = ['id_usuario' => $otro->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Otro'];

        // Invitado: a ingresar
        $this->put('/solicitudes/' . $solicitud->ID_Solicitud . '/elegir')->assertRedirect('/ingresar');

        // Otro particular (por ejemplo, el propio solicitante): 403
        $this->withSession($sesionOtro)->put('/solicitudes/' . $solicitud->ID_Solicitud . '/elegir')->assertForbidden();
        $this->withSession($sesionOtro)->put('/solicitudes/' . $solicitud->ID_Solicitud . '/rechazar')->assertForbidden();

        // El Administrador ve las solicitudes pero no las responde (no está en el grupo de la ruta)
        $admin = Administrador::factory()->create();
        $this->withSession(['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'])
            ->put('/solicitudes/' . $solicitud->ID_Solicitud . '/elegir')
            ->assertForbidden();

        $this->assertEquals('Pendiente', $solicitud->fresh()->Estado_Solicitud);
        $this->assertEquals('En adopción', $this->estadoMascota());
    }

    public function test_el_publicante_ve_los_botones_y_el_administrador_no()
    {
        $this->crearSolicitud();

        $this->withSession($this->sesionPublicante())
            ->get('/publicaciones/' . $this->publicacion->ID_Publicacion . '/solicitudes')
            ->assertSee('Elegir como adoptante')
            ->assertSee('Rechazar');

        $admin = Administrador::factory()->create();
        $this->withSession(['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'])
            ->get('/publicaciones/' . $this->publicacion->ID_Publicacion . '/solicitudes')
            ->assertOk()
            ->assertDontSee('Elegir como adoptante');
    }
}
