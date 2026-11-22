<?php

namespace Tests\Feature;

use App\Models\Adopcion;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\Refugio;
use App\Models\Seguimiento;
use App\Models\SolicitudAdopcion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Archivar y reactivar una mascota en adopción desde "Mi cuenta" (RF:0018, RF:0025, RF:0045)
class EstadoAdopcionTest extends TestCase
{
    use RefreshDatabase;

    private $publicante;
    private $publicacion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicante = Particular::factory()->create();
        $this->publicacion = $this->crearAdopcion($this->publicante->ID_Usuario);
    }

    private function crearAdopcion($idUsuario)
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Perro', 'Raza' => 'Caniche', 'Sexo' => 'Macho']);
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

    private function sesion($idUsuario, $tipo = 'Particular')
    {
        return ['id_usuario' => $idUsuario, 'tipo' => $tipo, 'nombre' => 'Ana'];
    }

    private function estado()
    {
        return PublicacionAdopcion::find($this->publicacion->ID_Publicacion)->Estado_Adopcion;
    }

    private function url($accion)
    {
        return '/adopciones/' . $this->publicacion->ID_Publicacion . '/' . $accion;
    }

    public function test_rf0018_archivar_la_saca_del_sitio_sin_borrarla()
    {
        $this->withSession($this->sesion($this->publicante->ID_Usuario))->put($this->url('archivar'))->assertSessionHas('exito');

        $this->assertEquals('Archivado', $this->estado());
        $this->assertDatabaseHas('Publicacion_Animal', ['ID_Publicacion' => $this->publicacion->ID_Publicacion]);
        $this->get('/adopcion')->assertDontSee('Caniche');
    }

    public function test_rf0045_reactivar_la_vuelve_a_en_adopcion()
    {
        $sesion = $this->sesion($this->publicante->ID_Usuario);
        $this->withSession($sesion)->put($this->url('archivar'));

        $this->withSession($sesion)->put($this->url('reactivar'))->assertSessionHas('exito');

        $this->assertEquals('En adopción', $this->estado());
        $this->get('/adopcion')->assertSee('Caniche');
    }

    public function test_rf0025_el_refugio_tambien_archiva_y_reactiva()
    {
        $refugio = Refugio::factory()->validado()->create();
        $this->publicacion = $this->crearAdopcion($refugio->ID_Usuario);
        $sesion = $this->sesion($refugio->ID_Usuario, 'Refugio');

        $this->withSession($sesion)->put($this->url('archivar'));
        $this->assertEquals('Archivado', $this->estado());

        $this->withSession($sesion)->put($this->url('reactivar'));
        $this->assertEquals('En adopción', $this->estado());
    }

    public function test_solo_se_ofrecen_las_transiciones_validas()
    {
        $sesion = $this->sesion($this->publicante->ID_Usuario);

        // En adopción no se reactiva
        $this->withSession($sesion)->put($this->url('reactivar'))->assertSessionHas('error');

        // Archivado no se vuelve a archivar
        $this->withSession($sesion)->put($this->url('archivar'));
        $this->withSession($sesion)->put($this->url('archivar'))->assertSessionHas('error');
        $this->assertEquals('Archivado', $this->estado());
    }

    public function test_una_adoptada_no_se_archiva_ni_se_reactiva()
    {
        $adopcion = PublicacionAdopcion::find($this->publicacion->ID_Publicacion);
        $adopcion->Estado_Adopcion = 'Adoptado';
        $adopcion->save();
        $sesion = $this->sesion($this->publicante->ID_Usuario);

        $this->withSession($sesion)->put($this->url('archivar'))->assertSessionHas('error');
        $this->withSession($sesion)->put($this->url('reactivar'))->assertSessionHas('error');
        $this->assertEquals('Adoptado', $this->estado());
    }

    public function test_mi_cuenta_muestra_el_boton_segun_el_estado()
    {
        $sesion = $this->sesion($this->publicante->ID_Usuario);

        $this->withSession($sesion)->get('/cuenta')->assertSee($this->url('archivar'))->assertDontSee($this->url('reactivar'));

        $this->withSession($sesion)->put($this->url('archivar'));
        $this->withSession($sesion)->get('/cuenta')->assertSee($this->url('reactivar'))->assertDontSee($this->url('archivar'));
    }

    public function test_solo_el_publicante_cambia_el_estado()
    {
        $otro = Particular::factory()->create();

        $this->put($this->url('archivar'))->assertRedirect('/ingresar');
        $this->withSession($this->sesion($otro->ID_Usuario))->put($this->url('archivar'))->assertForbidden();

        $this->assertEquals('En adopción', $this->estado());
    }

    public function test_rf0058_con_la_cuenta_restringida_no_se_reactiva()
    {
        $sesion = $this->sesion($this->publicante->ID_Usuario);
        $this->withSession($sesion)->put($this->url('archivar'));

        // El publicante adoptó otra mascota y no envió la foto: seguimiento vencido
        $otraMascota = $this->crearAdopcion(Particular::factory()->create()->ID_Usuario);
        $solicitud = new SolicitudAdopcion(['ID_Publicacion' => $otraMascota->ID_Publicacion]);
        $solicitud->ID_Usuario = $this->publicante->ID_Usuario;
        $solicitud->save();
        $adopcion = Adopcion::create(['ID_Solicitud' => $solicitud->ID_Solicitud, 'Fecha_Adopcion' => now()->subDays(30)->toDateString()]);
        Seguimiento::create([
            'ID_Adopcion' => $adopcion->ID_Adopcion,
            'Fecha_Aviso' => now()->subDays(15)->toDateString(),
            'Fecha_Limite' => now()->subDays(8)->toDateString(),
        ]);

        $this->withSession($sesion)->put($this->url('reactivar'))->assertSessionHas('error', Seguimiento::MENSAJE_RESTRINGIDA);
        $this->assertEquals('Archivado', $this->estado());
    }
}
