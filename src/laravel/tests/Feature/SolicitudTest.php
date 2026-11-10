<?php

namespace Tests\Feature;

use App\Mail\SolicitudAdopcionRecibida;
use App\Models\Administrador;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;
use App\Models\SolicitudAdopcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Solicitudes de adopción: solicitar (RF:0049, RF:0063, CP-22), solicitudes recibidas
 * (RF:0050, RNF:0013, CP-23) y enviadas (RF:0054, CP-26).
 */
class SolicitudTest extends TestCase
{
    use RefreshDatabase;

    private $publicante;
    private $solicitante;
    private $publicacion;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->publicante = Particular::factory()->create();
        $this->solicitante = Particular::factory()->create();
        $this->publicacion = $this->crearAdopcion($this->publicante->usuario);
    }

    private function crearAdopcion($usuario, $estado = 'En adopción')
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Perro', 'Raza' => 'Mestizo', 'Sexo' => 'Macho']);
        $publicacion->ID_Usuario = $usuario->ID_Usuario;
        $publicacion->Tipo_Publicacion = 'Adopción';
        $publicacion->save();

        $adopcion = new PublicacionAdopcion([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Edad_Meses' => 8,
            'Tamaño' => 'Mediano',
            'Estado_Salud' => 'Sano',
            'Estado_Esterilizacion' => 'Esterilizado',
            'Ubicacion_Aproximada' => 'Pocitos',
        ]);
        $adopcion->Estado_Adopcion = $estado;
        $adopcion->save();

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/uno.jpg']);

        return $publicacion;
    }

    private function sesion($particular)
    {
        return ['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => $particular->Nombre];
    }

    private function urlSolicitar()
    {
        return '/publicaciones/' . $this->publicacion->ID_Publicacion . '/solicitar';
    }

    // ---------- Botón en el detalle ----------

    public function test_el_invitado_ve_el_boton_y_al_tocarlo_se_le_pide_ingresar()
    {
        $this->get('/publicaciones/' . $this->publicacion->ID_Publicacion)->assertSee('Solicitar adopción');
        $this->get($this->urlSolicitar())->assertRedirect('/ingresar');
    }

    public function test_el_refugio_no_ve_el_boton_y_no_puede_solicitar()
    {
        $refugio = Refugio::factory()->validado()->create();
        $sesion = ['id_usuario' => $refugio->ID_Usuario, 'tipo' => 'Refugio', 'nombre' => 'Refugio'];

        $this->withSession($sesion)->get('/publicaciones/' . $this->publicacion->ID_Publicacion)->assertDontSee('Solicitar adopción');
        $this->withSession($sesion)->post($this->urlSolicitar(), ['acepto' => '1'])->assertForbidden();

        $this->assertDatabaseCount('Solicitud_Adopcion', 0);
    }

    public function test_el_publicante_ve_sus_solicitudes_en_vez_del_boton()
    {
        $this->withSession($this->sesion($this->publicante))
            ->get('/publicaciones/' . $this->publicacion->ID_Publicacion)
            ->assertDontSee('Solicitar adopción')
            ->assertSee('Ver solicitudes recibidas (0)');
    }

    // ---------- Solicitar (CP-22) ----------

    public function test_la_pantalla_muestra_los_datos_que_se_comparten_pero_no_la_cedula()
    {
        $this->withSession($this->sesion($this->solicitante))->get($this->urlSolicitar())
            ->assertOk()
            ->assertSee($this->solicitante->Nombre)
            ->assertSee($this->solicitante->Telefono)
            ->assertSee($this->solicitante->usuario->Email)
            ->assertDontSee($this->solicitante->CI);
    }

    public function test_cp22_la_solicitud_queda_pendiente_y_el_publicante_recibe_un_correo()
    {
        $this->withSession($this->sesion($this->solicitante))
            ->post($this->urlSolicitar(), ['acepto' => '1'])
            ->assertRedirect('/solicitudes');

        $this->assertDatabaseHas('Solicitud_Adopcion', [
            'ID_Publicacion' => $this->publicacion->ID_Publicacion,
            'ID_Usuario' => $this->solicitante->ID_Usuario,
            'Estado_Solicitud' => 'Pendiente',
        ]);

        Mail::assertSent(SolicitudAdopcionRecibida::class, 1);
        Mail::assertSent(SolicitudAdopcionRecibida::class, function ($correo)
        {
            return $correo->hasTo($this->publicante->usuario->Email);
        });
    }

    public function test_cp22_sin_aceptar_compartir_los_datos_no_se_envia()
    {
        $this->withSession($this->sesion($this->solicitante))
            ->post($this->urlSolicitar(), [])
            ->assertSessionHasErrors('acepto');

        $this->assertDatabaseCount('Solicitud_Adopcion', 0);
        Mail::assertNothingSent();
    }

    public function test_cp22_no_se_puede_solicitar_la_propia_publicacion()
    {
        $this->withSession($this->sesion($this->publicante))
            ->post($this->urlSolicitar(), ['acepto' => '1'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('Solicitud_Adopcion', 0);
    }

    public function test_cp22_no_se_pueden_tener_dos_solicitudes_pendientes_sobre_la_misma()
    {
        $this->withSession($this->sesion($this->solicitante))->post($this->urlSolicitar(), ['acepto' => '1']);
        $this->withSession($this->sesion($this->solicitante))
            ->post($this->urlSolicitar(), ['acepto' => '1'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('Solicitud_Adopcion', 1);

        // Y el detalle ya no ofrece el botón
        $this->withSession($this->sesion($this->solicitante))
            ->get('/publicaciones/' . $this->publicacion->ID_Publicacion)
            ->assertSee('Ya enviaste una solicitud');
    }

    public function test_rne18_solo_se_solicita_una_mascota_en_adopcion()
    {
        $adoptada = $this->crearAdopcion($this->publicante->usuario, 'Adoptado');

        $this->withSession($this->sesion($this->solicitante))
            ->post('/publicaciones/' . $adoptada->ID_Publicacion . '/solicitar', ['acepto' => '1'])
            ->assertNotFound();

        // Una mascota perdida tampoco se puede solicitar
        $perdida = new PublicacionAnimal(['Especie' => 'Gato', 'Raza' => 'Siamés', 'Sexo' => 'Hembra']);
        $perdida->ID_Usuario = $this->publicante->ID_Usuario;
        $perdida->Tipo_Publicacion = 'Perdida';
        $perdida->save();
        $aviso = new PublicacionPerdida([
            'ID_Publicacion' => $perdida->ID_Publicacion, 'Tipo_Aviso' => 'Perdida', 'Nombre_Mascota' => 'Luna',
            'Caracteristicas' => 'Collar', 'Fecha_Suceso' => now()->toDateString(), 'Zona_Suceso' => 'Centro', 'Contacto' => '099000000',
        ]);
        $aviso->Fecha_Vencimiento = now()->addDays(30)->toDateString();
        $aviso->save();

        $this->withSession($this->sesion($this->solicitante))
            ->post('/publicaciones/' . $perdida->ID_Publicacion . '/solicitar', ['acepto' => '1'])
            ->assertNotFound();

        $this->assertDatabaseCount('Solicitud_Adopcion', 0);
    }

    // ---------- Recibidas (CP-23) ----------

    public function test_cp23_el_publicante_ve_nombre_telefono_correo_fecha_y_estado()
    {
        $this->withSession($this->sesion($this->solicitante))->post($this->urlSolicitar(), ['acepto' => '1']);

        foreach (['/publicaciones/' . $this->publicacion->ID_Publicacion . '/solicitudes', '/solicitudes/recibidas'] as $url)
        {
            $this->withSession($this->sesion($this->publicante))->get($url)
                ->assertOk()
                ->assertSee($this->solicitante->Nombre . ' ' . $this->solicitante->Apellido)
                ->assertSee($this->solicitante->Telefono)
                ->assertSee($this->solicitante->usuario->Email)
                ->assertSee(now()->format('d/m/Y'))
                ->assertSee('Pendiente');
        }
    }

    public function test_cp23_otro_usuario_recibe_403_y_el_administrador_puede_verla()
    {
        $url = '/publicaciones/' . $this->publicacion->ID_Publicacion . '/solicitudes';

        $this->withSession($this->sesion($this->solicitante))->get($url)->assertForbidden();

        $admin = Administrador::factory()->create();
        $this->withSession(['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'])
            ->get($url)->assertOk();
    }

    public function test_cp23_la_lista_general_no_muestra_solicitudes_de_publicaciones_ajenas()
    {
        $this->withSession($this->sesion($this->solicitante))->post($this->urlSolicitar(), ['acepto' => '1']);
        $otro = Particular::factory()->create();

        $this->withSession($this->sesion($otro))->get('/solicitudes/recibidas')
            ->assertOk()
            ->assertDontSee($this->solicitante->Telefono);
    }

    // ---------- Enviadas (CP-26) ----------

    public function test_cp26_el_solicitante_ve_solo_sus_solicitudes_con_el_estado_actualizado()
    {
        $this->withSession($this->sesion($this->solicitante))->post($this->urlSolicitar(), ['acepto' => '1']);

        // Otra persona solicita otra mascota
        $otra = $this->crearAdopcion($this->publicante->usuario);
        $otro = Particular::factory()->create();
        $this->withSession($this->sesion($otro))->post('/publicaciones/' . $otra->ID_Publicacion . '/solicitar', ['acepto' => '1']);

        // El estado cambia (lo hará RF:0051/RF:0052) y la lista lo muestra
        $solicitud = SolicitudAdopcion::where('ID_Usuario', $this->solicitante->ID_Usuario)->first();
        $solicitud->Estado_Solicitud = 'Rechazada';
        $solicitud->save();

        $pagina = $this->withSession($this->sesion($this->solicitante))->get('/solicitudes');
        $pagina->assertOk()->assertSee('Rechazada')->assertDontSee('Pendiente');
        $this->assertCount(1, SolicitudAdopcion::where('ID_Usuario', $this->solicitante->ID_Usuario)->get());
    }
}
