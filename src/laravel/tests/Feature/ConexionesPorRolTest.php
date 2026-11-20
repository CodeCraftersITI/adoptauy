<?php

namespace Tests\Feature;

use App\Models\Particular;
use App\Models\Refugio;
use App\Models\Usuario;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UsuarioGoogle;
use Tests\TestCase;

/**
 * Inicio de sesión y registro con los usuarios de MySQL de privilegio mínimo (16.3.4, RNF:0007,
 * RNF:0013): adoptauy_login para RF:0022 y RF:0066, adoptauy_publico para RF:0034, RF:0039 y RF:0066.
 *
 * Corre con los usuarios reales, no con la conexión de la aplicación: así una consulta que
 * pida algo que ese usuario no tiene permitido hace fallar la prueba. Necesita que existan los
 * usuarios (docs/base-de-datos/usuarios.sql) y DB_LOGIN_USERNAME y DB_PUBLICO_USERNAME en el
 * entorno, como en GitHub Actions; si no, se saltea.
 *
 * Las conexiones de MySQL son distintas, así que no hay una transacción común que deshacer:
 * los datos se guardan de verdad y se borran al terminar cada prueba (solo los de esta prueba,
 * que usan el dominio conexiones.test).
 */
class ConexionesPorRolTest extends TestCase
{
    use RefreshDatabase;

    protected bool $conexionesReales = true;

    // Sin transacción: el usuario de login tiene que ver lo que la prueba guardó
    protected $connectionsToTransact = [];

    protected function setUp(): void
    {
        parent::setUp();

        $login = config('database.connections.mysql_login.username');
        $publico = config('database.connections.mysql_publico.username');
        $aplicacion = config('database.connections.mysql.username');

        if ($login == $aplicacion || $publico == $aplicacion)
        {
            $this->markTestSkipped('Faltan DB_LOGIN_USERNAME y DB_PUBLICO_USERNAME (usuarios de docs/base-de-datos/usuarios.sql).');
        }

        Mail::fake();
        Storage::fake();
    }

    protected function tearDown(): void
    {
        $ids = DB::table('Usuario')->where('Email', 'like', '%@conexiones.test')->pluck('ID_Usuario');

        DB::table('Refugio_Datos_Contacto')->whereIn('ID_Usuario', $ids)->delete();
        DB::table('Refugio')->whereIn('ID_Usuario', $ids)->delete();
        DB::table('Particular')->whereIn('ID_Usuario', $ids)->delete();
        DB::table('Usuario')->whereIn('ID_Usuario', $ids)->delete();

        parent::tearDown();
    }

    private function particular($email, $cambios = [])
    {
        $usuario = Usuario::factory()->create(array_merge(['Email' => $email], $cambios));
        Particular::factory()->create(['ID_Usuario' => $usuario->ID_Usuario]);

        return $usuario;
    }

    private function simularGoogle($id, $email)
    {
        $google = new UsuarioGoogle();
        $google->setRaw([
            'sub' => $id,
            'email' => $email,
            'email_verified' => true,
            'given_name' => 'Elena',
            'family_name' => 'Martínez',
            'name' => 'Elena Martínez',
        ])->map(['id' => $id, 'email' => $email, 'name' => 'Elena Martínez']);

        Socialite::shouldReceive('driver->user')->andReturn($google);
    }

    public function test_cada_conexion_entra_con_su_usuario_de_mysql()
    {
        $this->assertStringStartsWith('adoptauy_login@', DB::connection(Usuario::CONEXION_LOGIN)->selectOne('SELECT CURRENT_USER() AS u')->u);
        $this->assertStringStartsWith('adoptauy_publico@', DB::connection(Usuario::CONEXION_REGISTRO)->selectOne('SELECT CURRENT_USER() AS u')->u);
    }

    public function test_rf0022_inicia_sesion_con_adoptauy_login()
    {
        $this->particular('ana@conexiones.test');

        $this->post('/ingresar', ['email' => 'ana@conexiones.test', 'password' => UsuarioFactory::CONTRASEÑA])
            ->assertRedirect('/cuenta');

        $this->assertNotNull(session('id_usuario'));
    }

    public function test_rf0022_un_refugio_validado_inicia_sesion_con_adoptauy_login()
    {
        $usuario = Usuario::factory()->create(['Email' => 'refugio@conexiones.test', 'Tipo_Usuario' => 'Refugio']);
        Refugio::factory()->validado()->create(['ID_Usuario' => $usuario->ID_Usuario]);

        $this->post('/ingresar', ['email' => 'refugio@conexiones.test', 'password' => UsuarioFactory::CONTRASEÑA])
            ->assertRedirect('/cuenta');
    }

    public function test_rnf0013_adoptauy_login_no_puede_leer_la_cedula_ni_la_fila_completa()
    {
        $this->particular('ana@conexiones.test');

        $this->expectException(QueryException::class);
        DB::connection(Usuario::CONEXION_LOGIN)->table('Particular')->select('CI')->first();
    }

    public function test_rnf0007_adoptauy_login_no_puede_modificar_la_contrasena()
    {
        $usuario = $this->particular('ana@conexiones.test');

        $this->expectException(QueryException::class);
        DB::connection(Usuario::CONEXION_LOGIN)->table('Usuario')
            ->where('ID_Usuario', $usuario->ID_Usuario)->update(['Contraseña' => null]);
    }

    public function test_rf0039_registra_un_particular_con_adoptauy_publico()
    {
        $this->post('/registro', [
            'ci' => '1.234.567-2',
            'nombre' => 'Ana',
            'apellido' => 'Suárez',
            'telefono' => '099 123 456',
            'email' => 'nueva@conexiones.test',
            'password' => 'clave-segura',
        ])->assertRedirect('/ingresar');

        $this->assertDatabaseHas('Usuario', ['Email' => 'nueva@conexiones.test', 'Tipo_Usuario' => 'Particular']);
        $this->assertDatabaseHas('Particular', ['CI' => '12345672']);
    }

    public function test_rf0039_el_correo_repetido_se_controla_con_adoptauy_publico()
    {
        $this->particular('ana@conexiones.test');

        $this->post('/registro', [
            'ci' => '1.234.567-2',
            'nombre' => 'Ana',
            'apellido' => 'Suárez',
            'telefono' => '099123456',
            'email' => 'ana@conexiones.test',
            'password' => 'clave-segura',
        ])->assertSessionHasErrors('email');
    }

    public function test_rf0034_registra_la_solicitud_de_refugio_con_adoptauy_publico()
    {
        $this->post('/registro-refugio', [
            'nombre_refugio' => 'Refugio de prueba',
            'contacto' => '099000000',
            'ubicacion' => 'Montevideo',
            'certificado' => UploadedFile::fake()->create('certificado.pdf', 100, 'application/pdf'),
            'comprobante' => UploadedFile::fake()->image('comprobante.jpg'),
            'email' => 'refugio@conexiones.test',
            'password' => 'clave-segura',
        ])->assertRedirect('/');

        $this->assertDatabaseHas('Usuario', ['Email' => 'refugio@conexiones.test', 'Tipo_Usuario' => 'Refugio']);
    }

    public function test_rnf0013_adoptauy_publico_no_puede_leer_nombres_ni_telefonos()
    {
        $this->particular('ana@conexiones.test');

        $this->expectException(QueryException::class);
        DB::connection(Usuario::CONEXION_REGISTRO)->table('Particular')->select('Telefono')->first();
    }

    public function test_rf0066_google_vincula_una_cuenta_existente_con_adoptauy_login()
    {
        $usuario = $this->particular('elena@conexiones.test');
        $this->simularGoogle('google-conexiones-1', 'elena@conexiones.test');

        $this->get('/auth/google/callback')->assertRedirect('/cuenta');

        $this->assertEquals('google-conexiones-1', Usuario::find($usuario->ID_Usuario)->Google_ID);
    }

    public function test_rf0066_google_crea_una_cuenta_nueva_con_adoptauy_publico()
    {
        $this->simularGoogle('google-conexiones-2', 'elena@conexiones.test');

        $this->get('/auth/google/callback')->assertRedirect('/auth/google/completar');

        $this->post('/auth/google/completar', [
            'ci' => '1.234.567-2',
            'telefono' => '099123456',
            'nombre' => 'Elena',
            'apellido' => 'Martínez',
        ])->assertRedirect('/cuenta');

        $this->assertDatabaseHas('Usuario', ['Email' => 'elena@conexiones.test', 'Google_ID' => 'google-conexiones-2', 'Contraseña' => null]);
    }
}
