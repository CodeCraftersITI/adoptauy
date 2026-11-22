<?php

namespace Tests\Feature;

use App\Models\Particular;
use App\Models\Refugio;
use App\Models\RefugioDatoContacto;
use App\Models\Usuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Mi perfil (RF:0016, RF:0029, RF:0067)
class PerfilTest extends TestCase
{
    use RefreshDatabase;

    private function sesion($id, $tipo = 'Particular')
    {
        return ['id_usuario' => $id, 'tipo' => $tipo, 'nombre' => 'Ana'];
    }

    // ---------- CP-16: datos ----------

    public function test_cp16_el_particular_cambia_nombre_apellido_y_telefono()
    {
        $particular = Particular::factory()->create();

        $this->withSession($this->sesion($particular->ID_Usuario))->get('/perfil')
            ->assertOk()
            ->assertSee($particular->Nombre);

        $this->withSession($this->sesion($particular->ID_Usuario))->put('/perfil/datos', [
            'nombre' => 'Valentina',
            'apellido' => 'Suárez',
            'telefono' => '099 111 222',
        ])->assertRedirect('/perfil')->assertSessionHas('exito');

        $particular->refresh();
        $this->assertEquals('Valentina', $particular->Nombre);
        $this->assertEquals('099111222', $particular->Telefono);
        $this->assertEquals('Valentina Suárez', session('nombre'));
    }

    public function test_cp16_la_cedula_y_el_correo_no_cambian_aunque_se_envien()
    {
        $particular = Particular::factory()->create();
        $ci = $particular->CI;
        $correo = $particular->usuario->Email;

        $this->withSession($this->sesion($particular->ID_Usuario))->put('/perfil/datos', [
            'nombre' => 'Valentina',
            'apellido' => 'Suárez',
            'telefono' => '099111222',
            'ci' => '12345672',
            'CI' => '12345672',
            'email' => 'otro@prueba.uy',
            'Email' => 'otro@prueba.uy',
        ]);

        $this->assertEquals($ci, $particular->fresh()->CI);
        $this->assertEquals($correo, $particular->usuario->fresh()->Email);
    }

    public function test_cp16_se_aplican_las_mismas_reglas_que_en_el_registro()
    {
        $particular = Particular::factory()->create();

        $this->withSession($this->sesion($particular->ID_Usuario))->put('/perfil/datos', [
            'nombre' => 'A',
            'apellido' => str_repeat('a', 51),
            'telefono' => '1234567',
        ])->assertSessionHasErrors(['nombre', 'apellido', 'telefono']);
    }

    public function test_cp16_el_refugio_cambia_nombre_ubicacion_contactos_y_foto()
    {
        Storage::fake('public');
        $refugio = Refugio::factory()->validado()->create();
        RefugioDatoContacto::create(['ID_Usuario' => $refugio->ID_Usuario, 'Dato_Contacto' => '099000000']);

        $this->withSession($this->sesion($refugio->ID_Usuario, 'Refugio'))->put('/perfil/datos', [
            'nombre_refugio' => 'Refugio de prueba Nuevo',
            'ubicacion' => 'Prado, Montevideo',
            'contactos' => "098123456\n@refugio_prueba\n\n098123456",
            'foto' => UploadedFile::fake()->image('refugio.jpg'),
        ])->assertSessionHas('exito');

        $refugio->refresh();
        $this->assertEquals('Refugio de prueba Nuevo', $refugio->Nombre_Refugio);
        $this->assertEquals('Prado, Montevideo', $refugio->Ubicacion);
        // Las líneas vacías y repetidas no se guardan
        $this->assertEqualsCanonicalizing(['098123456', '@refugio_prueba'], $refugio->contactos()->pluck('Dato_Contacto')->all());
        Storage::disk('public')->assertExists($refugio->URL_Foto);

        // El perfil público muestra el nombre nuevo y la foto
        $this->get('/refugios/' . $refugio->ID_Usuario)->assertSee('Refugio de prueba Nuevo')->assertSee($refugio->URL_Foto);
    }

    public function test_el_refugio_necesita_al_menos_un_contacto()
    {
        $refugio = Refugio::factory()->validado()->create();

        $this->withSession($this->sesion($refugio->ID_Usuario, 'Refugio'))->put('/perfil/datos', [
            'nombre_refugio' => 'Refugio de prueba',
            'ubicacion' => 'Montevideo',
            'contactos' => "  \n ",
        ])->assertSessionHasErrors('lista_contactos');
    }

    public function test_sin_sesion_no_se_entra_al_perfil()
    {
        $this->get('/perfil')->assertRedirect('/ingresar');
        $this->put('/perfil/datos', ['nombre' => 'X'])->assertRedirect('/ingresar');
    }

    // ---------- CP-50: contraseña ----------

    public function test_cp50_sin_contrasena_se_define_sin_pedir_la_actual_y_despues_se_ingresa()
    {
        // Cuenta creada con Google: sin contraseña (RNE27)
        $usuario = Usuario::factory()->create(['Contraseña' => null, 'Google_ID' => 'google-123']);
        Particular::factory()->create(['ID_Usuario' => $usuario->ID_Usuario]);

        $this->withSession($this->sesion($usuario->ID_Usuario))->get('/perfil')->assertSee('Definir contraseña');

        $this->withSession($this->sesion($usuario->ID_Usuario))->put('/perfil/contrasena', [
            'password' => 'nueva-clave',
            'password_confirmation' => 'nueva-clave',
        ])->assertSessionHas('exito');

        $hash = $usuario->fresh()->Contraseña;
        $this->assertStringStartsWith('$2y$', $hash);   // hash bcrypt (RNF:0007)
        $this->assertTrue(Hash::check('nueva-clave', $hash));

        $this->flushSession();
        $this->post('/ingresar', ['email' => $usuario->Email, 'password' => 'nueva-clave'])->assertRedirect('/cuenta');
    }

    public function test_cp50_con_contrasena_se_exige_la_actual_y_una_incorrecta_se_rechaza()
    {
        $particular = Particular::factory()->create();
        $sesion = $this->sesion($particular->ID_Usuario);

        $this->withSession($sesion)->put('/perfil/contrasena', [
            'password' => 'nueva-clave',
            'password_confirmation' => 'nueva-clave',
        ])->assertSessionHasErrors('password_actual');

        $this->withSession($sesion)->put('/perfil/contrasena', [
            'password_actual' => 'no-es-esta',
            'password' => 'nueva-clave',
            'password_confirmation' => 'nueva-clave',
        ])->assertSessionHasErrors('password_actual');

        $this->withSession($sesion)->put('/perfil/contrasena', [
            'password_actual' => UsuarioFactory::CONTRASEÑA,
            'password' => 'nueva-clave',
            'password_confirmation' => 'nueva-clave',
        ])->assertSessionHas('exito');

        $this->assertTrue(Hash::check('nueva-clave', $particular->usuario->fresh()->Contraseña));
    }

    public function test_cp50_acepta_8_y_72_caracteres_y_rechaza_7_y_73()
    {
        $usuario = Usuario::factory()->create(['Contraseña' => null, 'Google_ID' => 'google-456']);
        Particular::factory()->create(['ID_Usuario' => $usuario->ID_Usuario]);
        $sesion = $this->sesion($usuario->ID_Usuario);

        foreach ([7, 73] as $largo)
        {
            $clave = str_repeat('a', $largo);
            $this->withSession($sesion)->put('/perfil/contrasena', ['password' => $clave, 'password_confirmation' => $clave])
                ->assertSessionHasErrors('password');
        }

        foreach ([8, 72] as $largo)
        {
            $clave = str_repeat('b', $largo);
            $this->withSession($sesion)->put('/perfil/contrasena', [
                'password_actual' => $largo == 8 ? null : str_repeat('b', 8),
                'password' => $clave,
                'password_confirmation' => $clave,
            ])->assertSessionHasNoErrors();
        }
    }

    public function test_las_dos_contrasenas_nuevas_tienen_que_coincidir()
    {
        $particular = Particular::factory()->create();

        $this->withSession($this->sesion($particular->ID_Usuario))->put('/perfil/contrasena', [
            'password_actual' => UsuarioFactory::CONTRASEÑA,
            'password' => 'nueva-clave',
            'password_confirmation' => 'otra-clave',
        ])->assertSessionHasErrors('password');
    }

    public function test_d98_el_refugio_tambien_cambia_su_contrasena()
    {
        $refugio = Refugio::factory()->validado()->create();

        $this->withSession($this->sesion($refugio->ID_Usuario, 'Refugio'))->put('/perfil/contrasena', [
            'password_actual' => UsuarioFactory::CONTRASEÑA,
            'password' => 'clave-del-refugio',
            'password_confirmation' => 'clave-del-refugio',
        ])->assertSessionHas('exito');

        $this->assertTrue(Hash::check('clave-del-refugio', $refugio->usuario->fresh()->Contraseña));
    }
}
