<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Editar una publicación (RF:0019, RF:0026, RF:0003)
class EditarPublicacionTest extends TestCase
{
    use RefreshDatabase;

    private $publicante;
    private $adopcion;
    private $perdida;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->publicante = Particular::factory()->create();

        // Una mascota en adopción de 24 meses
        $this->adopcion = $this->crearPublicacion('Adopción');
        PublicacionAdopcion::create([
            'ID_Publicacion' => $this->adopcion->ID_Publicacion,
            'Edad_Meses' => 24,
            'Tamaño' => 'Mediano',
            'Estado_Salud' => 'Sano',
            'Estado_Esterilizacion' => 'Esterilizado',
            'Ubicacion_Aproximada' => 'Pocitos',
        ]);

        // Una mascota perdida
        $this->perdida = $this->crearPublicacion('Perdida');
        $aviso = new PublicacionPerdida([
            'ID_Publicacion' => $this->perdida->ID_Publicacion,
            'Tipo_Aviso' => 'Perdida',
            'Nombre_Mascota' => 'Luna',
            'Caracteristicas' => 'Collar rojo',
            'Fecha_Suceso' => now()->subDays(2)->toDateString(),
            'Zona_Suceso' => 'Malvín',
            'Contacto' => '098765432',
        ]);
        $aviso->Fecha_Vencimiento = now()->addDays(28)->toDateString();
        $aviso->save();
    }

    private function crearPublicacion($tipo)
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Perro', 'Raza' => 'Caniche', 'Sexo' => 'Macho']);
        $publicacion->ID_Usuario = $this->publicante->ID_Usuario;
        $publicacion->Tipo_Publicacion = $tipo;
        $publicacion->save();

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/vieja.jpg']);

        return $publicacion;
    }

    private function sesion($particular)
    {
        return ['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'];
    }

    private function datosAdopcion($cambios = [])
    {
        return array_merge([
            'especie' => 'Perro',
            'raza' => 'Beagle',
            'sexo' => 'Hembra',
            'edad' => 3,
            'edad_unidad' => 'años',
            'tamanio' => 'Grande',
            'salud' => 'Vacunas al día',
            'esterilizacion' => 'No esterilizado',
            'ubicacion' => 'Cordón',
        ], $cambios);
    }

    private function url($publicacion)
    {
        return '/publicaciones/' . $publicacion->ID_Publicacion;
    }

    public function test_el_formulario_viene_con_los_datos_cargados_y_la_edad_en_anios()
    {
        $this->withSession($this->sesion($this->publicante))->get($this->url($this->adopcion) . '/editar')
            ->assertOk()
            ->assertSee('Editar publicación')
            ->assertSee('value="Caniche"', false)
            ->assertSee('value="2"', false)         // 24 meses = 2 años
            ->assertDontSee('Mascota perdida');     // el tipo no se cambia
    }

    public function test_rf0019_el_publicante_guarda_los_cambios_y_las_fotos_quedan()
    {
        $this->withSession($this->sesion($this->publicante))
            ->put($this->url($this->adopcion), $this->datosAdopcion())
            ->assertRedirect($this->url($this->adopcion))
            ->assertSessionHas('exito');

        $this->assertDatabaseHas('Publicacion_Animal', ['ID_Publicacion' => $this->adopcion->ID_Publicacion, 'Raza' => 'Beagle', 'Sexo' => 'Hembra']);
        $this->assertDatabaseHas('Publicacion_Adopcion', [
            'ID_Publicacion' => $this->adopcion->ID_Publicacion,
            'Edad_Meses' => 36,
            'Ubicacion_Aproximada' => 'Cordón',
        ]);
        // Sin fotos nuevas, queda la que había
        $this->assertDatabaseHas('Foto_Animal', ['ID_Publicacion' => $this->adopcion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/vieja.jpg']);
        // El estado no cambia al editar
        $this->assertEquals('En adopción', $this->adopcion->adopcion->fresh()->Estado_Adopcion);
    }

    public function test_las_fotos_nuevas_reemplazan_a_las_anteriores()
    {
        $nuevas = [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')];

        $this->withSession($this->sesion($this->publicante))
            ->put($this->url($this->adopcion), $this->datosAdopcion(['fotos' => $nuevas]));

        $fotos = FotoAnimal::where('ID_Publicacion', $this->adopcion->ID_Publicacion)->get();
        $this->assertCount(2, $fotos);
        $this->assertNotContains('fotos-animales/vieja.jpg', $fotos->pluck('URL_Foto'));
    }

    public function test_se_aplican_las_mismas_validaciones_que_al_publicar()
    {
        $sesion = $this->sesion($this->publicante);

        $this->withSession($sesion)->put($this->url($this->adopcion), $this->datosAdopcion(['raza' => 'a']))->assertSessionHasErrors('raza');
        $this->withSession($sesion)->put($this->url($this->adopcion), $this->datosAdopcion(['fotos' => array_fill(0, 7, UploadedFile::fake()->image('x.jpg'))]))
            ->assertSessionHasErrors('fotos');

        $this->assertEquals('Caniche', $this->adopcion->fresh()->Raza);
    }

    public function test_editar_una_perdida_no_cambia_su_vencimiento()
    {
        $vence = $this->perdida->perdida->Fecha_Vencimiento;

        $this->withSession($this->sesion($this->publicante))->put($this->url($this->perdida), [
            'nombre_mascota' => 'Lunita',
            'especie' => 'Gato',
            'raza' => 'Siamés',
            'sexo' => 'Hembra',
            'caracteristicas' => 'Collar azul',
            'fecha' => now()->subDays(3)->toDateString(),
            'zona' => 'Buceo',
            'contacto' => '099 111 222',
        ])->assertSessionHasNoErrors();

        $aviso = $this->perdida->perdida->fresh();
        $this->assertEquals('Lunita', $aviso->Nombre_Mascota);
        $this->assertEquals('099111222', $aviso->Contacto);
        $this->assertEquals('Perdida', $aviso->Tipo_Aviso);
        $this->assertEquals($vence, $aviso->Fecha_Vencimiento);
    }

    public function test_rf0003_el_administrador_puede_editar()
    {
        $admin = Administrador::factory()->create();

        $this->withSession(['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'])
            ->put($this->url($this->adopcion), $this->datosAdopcion())
            ->assertSessionHas('exito');

        $this->assertEquals('Beagle', $this->adopcion->fresh()->Raza);
    }

    public function test_otro_usuario_no_puede_editar()
    {
        $otro = Particular::factory()->create();

        $this->get($this->url($this->adopcion) . '/editar')->assertRedirect('/ingresar');
        $this->withSession($this->sesion($otro))->get($this->url($this->adopcion) . '/editar')->assertForbidden();
        $this->withSession($this->sesion($otro))->put($this->url($this->adopcion), $this->datosAdopcion())->assertForbidden();

        $this->assertEquals('Caniche', $this->adopcion->fresh()->Raza);
    }

    public function test_una_adoptada_ya_no_se_edita()
    {
        $datos = $this->adopcion->adopcion;
        $datos->Estado_Adopcion = 'Adoptado';
        $datos->save();

        $this->withSession($this->sesion($this->publicante))->put($this->url($this->adopcion), $this->datosAdopcion())
            ->assertSessionHas('error');
        $this->assertEquals('Caniche', $this->adopcion->fresh()->Raza);

        $this->withSession($this->sesion($this->publicante))->get('/cuenta')->assertDontSee($this->url($this->adopcion) . '/editar');
    }
}
