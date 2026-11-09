<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\Particular;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Alta de mascota encontrada (RF:0064). Caso CP-47.
 * Una mascota encontrada es la de otra persona: se guarda en Publicacion_Perdida con
 * Tipo_Aviso = 'Encontrada', con las mismas reglas que una perdida (13.2.2) salvo el
 * nombre, que es opcional (RNE26).
 */
class MascotaEncontradaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

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

    // Datos de CP-47: perro encontrado sin chapita en el Prado (datos ficticios)
    private function datosEncontrada($cambios = [])
    {
        return array_merge([
            'nombre_mascota' => '',
            'especie' => 'Perro',
            'raza' => 'mestizo',
            'sexo' => 'Macho',
            'caracteristicas' => 'Marrón, collar verde sin chapita',
            'fecha_hallazgo' => now()->subDay()->toDateString(),
            'zona' => 'Prado',
            'contacto' => '2901 2345',
            'fotos' => $this->fotos(2),
        ], $cambios);
    }

    public function test_cp47_alta_valida_sin_nombre_queda_vigente_con_vencimiento_a_30_dias()
    {
        $this->withSession($this->sesionParticular())
            ->post('/publicar/encontrada', $this->datosEncontrada())
            ->assertSessionHasNoErrors()
            ->assertRedirect('/cuenta');

        $encontrada = PublicacionPerdida::first();
        $this->assertEquals('Encontrada', $encontrada->Tipo_Aviso);
        $this->assertEquals('Vigente', $encontrada->Estado_Perdida);
        $this->assertNull($encontrada->Nombre_Mascota);
        $this->assertEquals('Prado', $encontrada->Zona_Suceso);
        $this->assertEquals(now()->subDay()->toDateString(), $encontrada->Fecha_Suceso);
        $this->assertEquals(now()->addDays(30)->toDateString(), $encontrada->Fecha_Vencimiento);
        $this->assertEquals('29012345', $encontrada->Contacto);
        $this->assertEquals('Perdida', $encontrada->publicacion->Tipo_Publicacion);   // misma tabla, sin subtipo nuevo
        $this->assertCount(2, $encontrada->publicacion->fotos);

        // Es pública: un invitado la ve en el apartado de encontradas, con «Sin nombre»
        $this->flushSession();
        $this->get('/')->assertSeeInOrder(['Mascotas encontradas', 'Sin nombre', '2901 2345']);
    }

    public function test_cp47_el_nombre_es_opcional_en_la_encontrada_pero_obligatorio_en_la_perdida()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada(['nombre_mascota' => 'Toby']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('Publicacion_Perdida', ['Tipo_Aviso' => 'Encontrada', 'Nombre_Mascota' => 'Toby']);

        $perdida = $this->datosEncontrada(['fecha_extravio' => now()->subDay()->toDateString()]);
        $this->withSession($sesion)->post('/publicar/perdida', $perdida)->assertSessionHasErrors('nombre_mascota');
        $this->assertDatabaseCount('Publicacion_Perdida', 1);
    }

    public function test_cp47_mismos_limites_que_la_perdida()
    {
        $sesion = $this->sesionParticular();

        // Fotos: se aceptan 1 y 3, se rechazan 0 y 4 (RNE01)
        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada(['fotos' => $this->fotos(1)]))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada(['fotos' => $this->fotos(3)]))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada(['fotos' => []]))->assertSessionHasErrors('fotos');
        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada(['fotos' => $this->fotos(4)]))->assertSessionHasErrors('fotos');

        // Fecha del hallazgo futura, zona de 2 caracteres y contacto que no es un teléfono
        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada(['fecha_hallazgo' => now()->addDay()->toDateString()]))->assertSessionHasErrors('fecha_hallazgo');
        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada(['zona' => 'Pr']))->assertSessionHasErrors('zona');
        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada(['contacto' => 'luna@correo.uy']))->assertSessionHasErrors('contacto');

        $this->assertDatabaseCount('Publicacion_Perdida', 2);
    }

    // ---------- Acceso por rol (RNE16) ----------

    public function test_el_invitado_es_enviado_a_ingresar()
    {
        $this->get('/publicar/encontrada')->assertRedirect('/ingresar');
        $this->post('/publicar/encontrada', $this->datosEncontrada())->assertRedirect('/ingresar');
        $this->assertDatabaseCount('Publicacion_Animal', 0);
    }

    public function test_el_particular_y_el_refugio_publican_y_el_administrador_no()
    {
        $this->withSession($this->sesionParticular())->get('/publicar/encontrada')->assertOk()->assertSee('Mascota encontrada');

        $refugio = Refugio::factory()->validado()->create();
        $this->withSession(['id_usuario' => $refugio->ID_Usuario, 'tipo' => 'Refugio', 'nombre' => 'Refugio'])
            ->post('/publicar/encontrada', $this->datosEncontrada())
            ->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Publicacion_Perdida', 1);

        $admin = Administrador::factory()->create();
        $this->withSession(['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'])
            ->post('/publicar/encontrada', $this->datosEncontrada())
            ->assertForbidden();
        $this->assertDatabaseCount('Publicacion_Perdida', 1);
    }

    public function test_mi_cuenta_muestra_la_encontrada_con_su_tipo()
    {
        $sesion = $this->sesionParticular();
        $this->withSession($sesion)->post('/publicar/encontrada', $this->datosEncontrada());

        $this->withSession($sesion)->get('/cuenta')
            ->assertSee('Sin nombre')
            ->assertSee('Encontrada')
            ->assertSee('Vigente');
    }
}
