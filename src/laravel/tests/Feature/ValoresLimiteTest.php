<?php

namespace Tests\Feature;

use App\Models\Particular;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Valores límite de los campos del Hito 2 (RF:0039, RF:0017, RF:0046)
class ValoresLimiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ---------- Registro de Particular (RF:0039) ----------

    // Datos válidos ficticios; la CI 1.234.567-2 tiene dígito verificador correcto
    private function registro($cambios = [])
    {
        return array_merge([
            'ci' => '12345672',
            'nombre' => 'Ana',
            'apellido' => 'Suárez',
            'telefono' => '099123456',
            'email' => 'ana@prueba.uy',
            'password' => 'clave-segura',
        ], $cambios);
    }

    public function test_cp02_nombre_rechaza_1_caracter_y_acepta_2()
    {
        $this->post('/registro', $this->registro(['nombre' => 'A']))->assertSessionHasErrors('nombre');
        $this->assertDatabaseCount('Usuario', 0);

        $this->post('/registro', $this->registro(['nombre' => 'Al']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Usuario', 1);
    }

    public function test_cp02_apellido_rechaza_51_caracteres_y_acepta_50()
    {
        $this->post('/registro', $this->registro(['apellido' => str_repeat('a', 51)]))->assertSessionHasErrors('apellido');
        $this->assertDatabaseCount('Usuario', 0);

        $this->post('/registro', $this->registro(['apellido' => str_repeat('a', 50)]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Usuario', 1);
    }

    public function test_cp02_telefono_rechaza_7_y_10_digitos_y_acepta_8()
    {
        $this->post('/registro', $this->registro(['telefono' => '2901234']))->assertSessionHasErrors('telefono');
        $this->post('/registro', $this->registro(['telefono' => '0991234567']))->assertSessionHasErrors('telefono');
        $this->assertDatabaseCount('Usuario', 0);

        $this->post('/registro', $this->registro(['telefono' => '29012345']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Usuario', 1);
    }

    public function test_cp02_contrasena_rechaza_73_caracteres_y_acepta_72()
    {
        // bcrypt solo usa los primeros 72 bytes: una contraseña más larga se truncaría sin aviso
        $this->post('/registro', $this->registro(['password' => str_repeat('a', 73)]))->assertSessionHasErrors('password');
        $this->assertDatabaseCount('Usuario', 0);

        $this->post('/registro', $this->registro(['password' => str_repeat('a', 72)]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Usuario', 1);
    }

    // ---------- Publicaciones (RF:0017 y RF:0046) ----------

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

    private function adopcion($cambios = [])
    {
        return array_merge([
            'tipo' => 'Adopción',
            'especie' => 'Perro',
            'raza' => 'mestizo',
            'sexo' => 'Macho',
            'edad' => 8,
            'edad_unidad' => 'meses',
            'tamanio' => 'Mediano',
            'salud' => 'Sano',
            'esterilizacion' => 'Esterilizado',
            'ubicacion' => 'Pocitos',
            'fotos' => $this->fotos(1),
        ], $cambios);
    }

    private function perdida($cambios = [])
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
            'contacto' => '098765432',
            'fotos' => $this->fotos(1),
        ], $cambios);
    }

    public function test_cp07_raza_rechaza_1_caracter_y_acepta_2()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->adopcion(['raza' => 'a']))->assertSessionHasErrors('raza');
        $this->assertDatabaseCount('Publicacion_Animal', 0);

        $this->withSession($sesion)->post('/publicar', $this->adopcion(['raza' => 'Pug']))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar', $this->adopcion(['raza' => 'ab']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Publicacion_Animal', 2);
    }

    public function test_cp07_raza_rechaza_51_caracteres_y_acepta_50()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->adopcion(['raza' => str_repeat('a', 51)]))->assertSessionHasErrors('raza');
        $this->withSession($sesion)->post('/publicar', $this->adopcion(['raza' => str_repeat('a', 50)]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Publicacion_Animal', 1);
    }

    public function test_cp07_ubicacion_rechaza_2_caracteres_y_acepta_3()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->adopcion(['ubicacion' => 'Pr']))->assertSessionHasErrors('ubicacion');
        $this->assertDatabaseCount('Publicacion_Animal', 0);

        $this->withSession($sesion)->post('/publicar', $this->adopcion(['ubicacion' => 'Cerro']))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar', $this->adopcion(['ubicacion' => 'Paz']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Publicacion_Animal', 2);
    }

    public function test_cp07_ubicacion_rechaza_101_caracteres_y_acepta_100()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->adopcion(['ubicacion' => str_repeat('a', 101)]))->assertSessionHasErrors('ubicacion');
        $this->withSession($sesion)->post('/publicar', $this->adopcion(['ubicacion' => str_repeat('a', 100)]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Publicacion_Animal', 1);
    }

    public function test_cp07_edad_estimada_no_tiene_maximo()
    {
        // RNE12: Edad_Meses no puede ser negativa y no tiene máximo
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->adopcion(['edad' => -1]))->assertSessionHasErrors('edad');
        $this->withSession($sesion)->post('/publicar', $this->adopcion(['edad' => 31, 'edad_unidad' => 'años']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('Publicacion_Adopcion', ['Edad_Meses' => 372]);
    }

    public function test_rnf0010_una_edad_imposible_da_error_de_validacion_y_no_error_500()
    {
        // Edad_Meses es smallint sin signo (hasta 65535 meses)
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->adopcion(['edad' => 6000, 'edad_unidad' => 'años']))
            ->assertStatus(302)
            ->assertSessionHasErrors('edad');
        $this->assertDatabaseCount('Publicacion_Animal', 0);
    }

    public function test_cp10_zona_rechaza_2_caracteres_y_acepta_3()
    {
        $sesion = $this->sesionParticular();

        $this->withSession($sesion)->post('/publicar', $this->perdida(['zona' => 'Pr']))->assertSessionHasErrors('zona');
        $this->assertDatabaseCount('Publicacion_Animal', 0);

        $this->withSession($sesion)->post('/publicar', $this->perdida(['zona' => 'Paz']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Publicacion_Animal', 1);
    }

    public function test_cp10_contacto_es_un_telefono_de_8_o_9_digitos()
    {
        $sesion = $this->sesionParticular();

        foreach (['1234567', '0987654321', 'luna@correo.uy', 'Instagram @luna'] as $invalido)
        {
            $this->withSession($sesion)->post('/publicar', $this->perdida(['contacto' => $invalido]))->assertSessionHasErrors('contacto');
        }
        $this->assertDatabaseCount('Publicacion_Animal', 0);

        $this->withSession($sesion)->post('/publicar', $this->perdida(['contacto' => '29012345']))->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/publicar', $this->perdida(['contacto' => '098 765 432']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('Publicacion_Animal', 2);
        $this->assertDatabaseHas('Publicacion_Perdida', ['Contacto' => '098765432']);
    }
}
