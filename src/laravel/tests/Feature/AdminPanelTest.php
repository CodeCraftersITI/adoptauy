<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel del Administrador: publicaciones (RF:0001, RF:0002, RF:0004, RF:0011, RF:0012),
 * refugios (RF:0006, RF:0008, RF:0009) y particulares (RF:0010, RF:0014).
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private $sesionAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = Administrador::factory()->create();
        $this->sesionAdmin = ['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'];
    }

    // Publicación en adopción de un usuario, con una foto
    private function crearAdopcion($idUsuario, $raza = 'Labrador')
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

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/prueba.jpg']);

        return $publicacion;
    }

    private function crearPerdida($idUsuario)
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Gato', 'Raza' => 'mestizo', 'Sexo' => 'Hembra']);
        $publicacion->ID_Usuario = $idUsuario;
        $publicacion->Tipo_Publicacion = 'Perdida';
        $publicacion->save();

        $perdida = new PublicacionPerdida([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Tipo_Aviso' => 'Perdida',
            'Nombre_Mascota' => 'Luna',
            'Caracteristicas' => 'Collar rojo',
            'Fecha_Suceso' => now()->subDay()->toDateString(),
            'Zona_Suceso' => 'Malvín',
            'Contacto' => '098765432',
        ]);
        $perdida->Fecha_Vencimiento = now()->addDays(30)->toDateString();
        $perdida->save();

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/prueba.jpg']);

        return $publicacion;
    }

    // ---------- Acceso por rol ----------

    public function test_solo_el_administrador_entra_a_las_pantallas_del_panel()
    {
        $particular = Particular::factory()->create();
        $refugio = Refugio::factory()->validado()->create();

        foreach (['/admin/publicaciones', '/admin/refugios', '/admin/particulares'] as $ruta)
        {
            $this->flushSession();   // sin sesión, como un invitado
            $this->get($ruta)->assertRedirect('/ingresar');

            $this->withSession(['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'])
                ->get($ruta)->assertForbidden();

            $this->withSession(['id_usuario' => $refugio->ID_Usuario, 'tipo' => 'Refugio', 'nombre' => 'Refugio'])
                ->get($ruta)->assertForbidden();

            $this->withSession($this->sesionAdmin)->get($ruta)->assertOk();
        }
    }

    // ---------- Publicaciones ----------

    public function test_el_listado_muestra_publicaciones_de_cualquier_estado()
    {
        $particular = Particular::factory()->create();
        $archivada = $this->crearAdopcion($particular->ID_Usuario, 'Caniche');
        $archivada->adopcion->Estado_Adopcion = 'Archivado';
        $archivada->adopcion->save();

        $this->withSession($this->sesionAdmin)->get('/admin/publicaciones')
            ->assertOk()
            ->assertSee('Caniche')
            ->assertSee('Archivado');
    }

    public function test_filtra_por_tipo_de_publicante()
    {
        $particular = Particular::factory()->create();
        $refugio = Refugio::factory()->validado()->create();
        $this->crearAdopcion($particular->ID_Usuario, 'Caniche');
        $this->crearAdopcion($refugio->ID_Usuario, 'Beagle');

        // RF:0012: solo las de refugios
        $this->withSession($this->sesionAdmin)->get('/admin/publicaciones?publicante=Refugio')
            ->assertSee('Beagle')
            ->assertDontSee('Caniche');

        // RF:0011: solo las de particulares
        $this->withSession($this->sesionAdmin)->get('/admin/publicaciones?publicante=Particular')
            ->assertSee('Caniche')
            ->assertDontSee('Beagle');
    }

    public function test_filtra_por_usuario_y_por_tipo()
    {
        $ana = Particular::factory()->create(['Nombre' => 'Anabela']);
        $otro = Particular::factory()->create(['Nombre' => 'Bruno']);
        $this->crearAdopcion($ana->ID_Usuario, 'Caniche');
        $this->crearAdopcion($otro->ID_Usuario, 'Beagle');
        $this->crearPerdida($otro->ID_Usuario);

        $this->withSession($this->sesionAdmin)->get('/admin/publicaciones?usuario=Anabela')
            ->assertSee('Caniche')
            ->assertDontSee('Beagle');

        $publicaciones = $this->withSession($this->sesionAdmin)->get('/admin/publicaciones?tipo=Perdida')->viewData('publicaciones');
        $this->assertEquals(1, $publicaciones->total());
    }

    public function test_rf0001_la_baja_archiva_la_publicacion_y_sale_de_la_consulta_publica()
    {
        $particular = Particular::factory()->create();
        $adopcion = $this->crearAdopcion($particular->ID_Usuario, 'Caniche');
        $perdida = $this->crearPerdida($particular->ID_Usuario);

        $this->withSession($this->sesionAdmin)->put('/admin/publicaciones/' . $adopcion->ID_Publicacion . '/archivar');
        $this->withSession($this->sesionAdmin)->put('/admin/publicaciones/' . $perdida->ID_Publicacion . '/archivar');

        $this->assertEquals('Archivado', $adopcion->adopcion->fresh()->Estado_Adopcion);
        $this->assertEquals('Archivada', $perdida->perdida->fresh()->Estado_Perdida);
        $this->get('/adopcion')->assertDontSee('Caniche');
    }

    public function test_no_se_da_de_baja_una_publicacion_adoptada()
    {
        $particular = Particular::factory()->create();
        $adopcion = $this->crearAdopcion($particular->ID_Usuario);
        $adopcion->adopcion->Estado_Adopcion = 'Adoptado';
        $adopcion->adopcion->save();

        $this->withSession($this->sesionAdmin)
            ->put('/admin/publicaciones/' . $adopcion->ID_Publicacion . '/archivar')
            ->assertSessionHas('error');

        $this->assertEquals('Adoptado', $adopcion->adopcion->fresh()->Estado_Adopcion);
    }

    // ---------- Refugios ----------

    public function test_lista_y_filtra_refugios_de_todos_los_estados()
    {
        Refugio::factory()->validado()->create(['Nombre_Refugio' => 'Refugio Uno de prueba', 'Ubicacion' => 'Prado']);
        Refugio::factory()->create(['Nombre_Refugio' => 'Refugio Dos de prueba', 'Ubicacion' => 'Cerro']);

        $this->withSession($this->sesionAdmin)->get('/admin/refugios')
            ->assertSee('Refugio Uno de prueba')
            ->assertSee('Refugio Dos de prueba');

        $this->withSession($this->sesionAdmin)->get('/admin/refugios?ubicacion=Cerro')
            ->assertSee('Refugio Dos de prueba')
            ->assertDontSee('Refugio Uno de prueba');
    }

    public function test_suspender_y_levantar_la_suspension()
    {
        $refugio = Refugio::factory()->validado()->create();

        $this->withSession($this->sesionAdmin)->put('/admin/refugios/' . $refugio->ID_Usuario . '/suspender');
        $this->assertEquals('Suspendido', $refugio->fresh()->Estado_Validacion);
        $this->get('/refugios/' . $refugio->ID_Usuario)->assertNotFound();

        $this->withSession($this->sesionAdmin)->put('/admin/refugios/' . $refugio->ID_Usuario . '/levantar');
        $this->assertEquals('Validado', $refugio->fresh()->Estado_Validacion);
    }

    public function test_rf0006_baja_del_refugio_archiva_sus_publicaciones_y_no_entra_mas()
    {
        $refugio = Refugio::factory()->validado()->create();
        $adopcion = $this->crearAdopcion($refugio->ID_Usuario);
        $perdida = $this->crearPerdida($refugio->ID_Usuario);

        $this->withSession($this->sesionAdmin)->put('/admin/refugios/' . $refugio->ID_Usuario . '/baja');

        $this->assertEquals('Dado de baja', $refugio->fresh()->Estado_Validacion);
        $this->assertEquals('Archivado', $adopcion->adopcion->fresh()->Estado_Adopcion);
        $this->assertEquals('Archivada', $perdida->perdida->fresh()->Estado_Perdida);

        $this->flushSession();
        $this->post('/ingresar', ['email' => $refugio->usuario->Email, 'password' => UsuarioFactory::CONTRASEÑA])
            ->assertSessionHas('error');
    }

    public function test_no_se_da_de_baja_un_refugio_pendiente()
    {
        $refugio = Refugio::factory()->create();

        $this->withSession($this->sesionAdmin)
            ->put('/admin/refugios/' . $refugio->ID_Usuario . '/baja')
            ->assertSessionHas('error');

        $this->assertEquals('Pendiente de validación', $refugio->fresh()->Estado_Validacion);
    }

    // ---------- Particulares ----------

    public function test_rf0014_lista_particulares_sin_mostrar_la_ci()
    {
        $particular = Particular::factory()->create(['Nombre' => 'Anabela']);

        $this->withSession($this->sesionAdmin)->get('/admin/particulares')
            ->assertSee('Anabela')
            ->assertSee($particular->usuario->Email)
            ->assertDontSee($particular->CI);
    }

    public function test_rf0010_baja_logica_del_particular_no_borra_y_no_puede_entrar()
    {
        $particular = Particular::factory()->create();

        $this->withSession($this->sesionAdmin)->put('/admin/particulares/' . $particular->ID_Usuario . '/baja');

        // Sigue en la base, con Estado_Usuario Baja
        $this->assertEquals('Baja', $particular->usuario->fresh()->Estado_Usuario);

        $this->flushSession();
        $this->post('/ingresar', ['email' => $particular->usuario->Email, 'password' => UsuarioFactory::CONTRASEÑA])
            ->assertSessionHas('error');
        $this->assertNull(session('id_usuario'));
    }
}
