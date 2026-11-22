<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\CuentaBancaria;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;
use App\Models\RefugioDatoContacto;
use App\Models\Seguimiento;
use App\Models\SolicitudAdopcion;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Usuarios de MySQL con privilegio mínimo (docs/base-de-datos/usuarios.sql)
class ConexionesPorRolTest extends TestCase
{
    use DatabaseTruncation;

    protected $connectionsToTruncate = ['mysql'];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.conexiones_por_rol' => true, 'app.limites' => false]);
        Mail::fake();
        Storage::fake('public');
    }

    // DatabaseTruncation vacía las tablas antes de cada prueba, no después
    protected function tearDown(): void
    {
        DB::setDefaultConnection('mysql');
        $this->truncateTablesForAllConnections();
        parent::tearDown();
    }

    // Después de cada pedido, la prueba vuelve a la conexión "mysql" para preparar datos
    public function call($method, $uri, $parameters = [], $cookies = [], $files = [], $server = [], $content = null)
    {
        $respuesta = parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
        DB::setDefaultConnection('mysql');

        return $respuesta;
    }

    private function sesion($id, $tipo)
    {
        return ['id_usuario' => $id, 'tipo' => $tipo, 'nombre' => 'Prueba'];
    }

    private function crearAdopcion($idUsuario, $raza = 'Caniche')
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
        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/uno.jpg']);

        return $publicacion;
    }

    private function refugioValidado($nombre = 'Refugio de prueba Prado')
    {
        $refugio = Refugio::factory()->validado()->create(['Nombre_Refugio' => $nombre]);
        RefugioDatoContacto::create(['ID_Usuario' => $refugio->ID_Usuario, 'Dato_Contacto' => '099000000']);

        return $refugio;
    }

    // ---------- Lo prohibido sigue prohibido ----------

    public function test_cada_usuario_de_mysql_tiene_solo_sus_permisos()
    {
        $prohibido = function ($conexion, $sql)
        {
            try
            {
                DB::connection($conexion)->statement($sql);
                $this->fail($conexion . ' pudo ejecutar: ' . $sql);
            }
            catch (QueryException $e)
            {
                $this->assertStringContainsString('denied', $e->getMessage());
            }
        };

        $prohibido('publico', 'SELECT CI, Nombre FROM Particular');          // el invitado no ve nombres
        $prohibido('publico', 'SELECT Certificado_Personeria FROM Refugio');  // ni documentos de refugios
        $prohibido('login', 'SELECT CI FROM Particular');                     // el login no ve datos personales
        $prohibido('particular', 'SELECT CI FROM Particular');                // nadie ve cédulas ajenas (RNF:0013)
        $prohibido('particular', 'DELETE FROM Usuario WHERE ID_Usuario = 0'); // baja lógica: sin DELETE
        $prohibido('particular', 'UPDATE Refugio SET Estado_Validacion = "Validado" WHERE ID_Usuario = 0');
        $prohibido('refugio', 'UPDATE Refugio SET Estado_Validacion = "Validado" WHERE ID_Usuario = 0');
        $prohibido('refugio', 'INSERT INTO Solicitud_Adopcion (ID_Publicacion, ID_Usuario) VALUES (0, 0)');
        $prohibido('admin', 'DELETE FROM Publicacion_Animal WHERE ID_Publicacion = 0');
        $prohibido('admin', 'DROP TABLE Reporte');                            // solo migraciones cambia tablas
        $prohibido('sistema', 'DELETE FROM Seguimiento WHERE ID_Adopcion = 0');
    }

    // ---------- Invitado (adoptauy_publico) y login (adoptauy_login) ----------

    public function test_el_invitado_navega_y_se_registra_y_despues_inicia_sesion()
    {
        $refugio = $this->refugioValidado();
        $publicacion = $this->crearAdopcion($refugio->ID_Usuario, 'Beagle');

        $this->get('/')->assertOk()->assertSee('Beagle');
        $this->get('/adopcion?especie=Perro&edad=adulto')->assertOk()->assertSee('Beagle');
        $this->get('/perdidas')->assertOk();
        $this->get('/refugios')->assertOk()->assertSee('Refugio de prueba Prado');
        $this->get('/refugios/' . $refugio->ID_Usuario)->assertOk()->assertSee('099000000');
        $this->get('/publicaciones/' . $publicacion->ID_Publicacion)->assertOk()->assertSee('Refugio Refugio de prueba Prado');

        // Registro con adoptauy_publico
        $this->post('/registro', [
            'ci' => '12345672', 'nombre' => 'Ana', 'apellido' => 'Suárez', 'telefono' => '099123456',
            'email' => 'ana@prueba.uy', 'password' => 'clave-segura',
        ])->assertRedirect('/ingresar')->assertSessionHasNoErrors();

        // Confirmación del correo con adoptauy_login
        $ana = \App\Models\Usuario::where('Email', 'ana@prueba.uy')->first();
        $enlace = \Illuminate\Support\Facades\URL::signedRoute('correo.confirmar', ['id' => $ana->ID_Usuario]);
        $this->get($enlace)->assertSessionHas('exito');

        // Inicio de sesión con adoptauy_login
        $this->post('/ingresar', ['email' => 'ana@prueba.uy', 'password' => 'clave-segura'])->assertRedirect('/cuenta');
        $this->assertEquals('Ana Suárez', session('nombre'));
        $this->get('/cuenta')->assertOk()->assertSee('Mi cuenta');
    }

    // Continuar con Google (RF:0066)
    private function simularGoogle($sub, $email)
    {
        $google = new \Laravel\Socialite\Two\User();
        $google->setRaw(['sub' => $sub, 'email' => $email, 'email_verified' => true, 'given_name' => 'Lucía', 'family_name' => 'Pereira'])
            ->map(['id' => $sub, 'email' => $email, 'name' => 'Lucía Pereira']);
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver->user')->andReturn($google);
    }

    public function test_continuar_con_google_registra_una_cuenta_nueva()
    {
        $this->simularGoogle('google-123', 'lucia@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect('/auth/google/completar');
        $this->post('/auth/google/completar', ['ci' => '12345672', 'telefono' => '099123456', 'nombre' => 'Lucía', 'apellido' => 'Pereira'])
            ->assertRedirect('/cuenta');

        $this->assertDatabaseHas('Usuario', ['Email' => 'lucia@gmail.com', 'Google_ID' => 'google-123']);
    }

    public function test_continuar_con_google_vincula_un_particular_existente()
    {
        $existente = Particular::factory()->create();
        $this->simularGoogle('google-456', $existente->usuario->Email);

        $this->get('/auth/google/callback')->assertRedirect('/cuenta');

        $this->assertEquals('google-456', $existente->usuario()->first()->Google_ID);
    }

    public function test_solicitud_de_registro_de_refugio_como_invitado()
    {
        Storage::fake('local');

        $this->post('/registro-refugio', [
            'nombre_refugio' => 'Refugio de prueba Nuevo', 'contacto' => '098111222', 'ubicacion' => 'Cerro',
            'certificado' => UploadedFile::fake()->create('cert.pdf', 10, 'application/pdf'),
            'comprobante' => UploadedFile::fake()->create('comp.pdf', 10, 'application/pdf'),
            'email' => 'nuevo@prueba.uy', 'password' => 'clave-segura',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('Refugio', ['Nombre_Refugio' => 'Refugio de prueba Nuevo', 'Estado_Validacion' => 'Pendiente de validación']);
    }

    // ---------- Particular (adoptauy_particular) y sistema (adoptauy_sistema) ----------

    public function test_el_particular_publica_solicita_reporta_y_edita_su_perfil()
    {
        $particular = Particular::factory()->create();
        $sesion = $this->sesion($particular->ID_Usuario, 'Particular');
        $otro = Particular::factory()->create();
        $ajena = $this->crearAdopcion($otro->ID_Usuario, 'Labrador');

        $this->withSession($sesion)->get('/cuenta')->assertOk();

        $this->withSession($sesion)->post('/publicar', [
            'tipo' => 'Perdida', 'nombre_mascota' => 'Luna', 'especie' => 'Gato', 'raza' => 'Siamés', 'sexo' => 'Hembra',
            'caracteristicas' => 'Collar rojo', 'fecha' => now()->subDay()->toDateString(), 'zona' => 'Malvín',
            'contacto' => '098765432', 'fotos' => [UploadedFile::fake()->image('a.jpg')],
        ])->assertRedirect('/cuenta')->assertSessionHasNoErrors();

        $mia = PublicacionAnimal::where('ID_Usuario', $particular->ID_Usuario)->first();
        $this->withSession($sesion)->put('/publicaciones/' . $mia->ID_Publicacion . '/renovar')->assertSessionHas('exito');
        $this->withSession($sesion)->get('/publicaciones/' . $mia->ID_Publicacion . '/editar')->assertOk();

        $this->withSession($sesion)->post('/publicaciones/' . $ajena->ID_Publicacion . '/solicitar', ['acepto' => '1'])
            ->assertRedirect('/solicitudes');
        $this->withSession($sesion)->get('/solicitudes')->assertOk()->assertSee('Labrador');

        $this->withSession($sesion)->get('/perfil')->assertOk()->assertDontSee($particular->CI);
        $this->withSession($sesion)->put('/perfil/datos', ['nombre' => 'Valentina', 'apellido' => 'Suárez', 'telefono' => '099111222'])
            ->assertSessionHas('exito');
        $this->withSession($sesion)->put('/perfil/contrasena', [
            'password_actual' => UsuarioFactory::CONTRASEÑA, 'password' => 'nueva-clave', 'password_confirmation' => 'nueva-clave',
        ])->assertSessionHas('exito');

        $this->assertEquals('Valentina', $particular->fresh()->Nombre);
    }

    public function test_tres_reportes_ocultan_la_publicacion_con_el_usuario_sistema()
    {
        $publicante = Particular::factory()->create();
        $publicacion = $this->crearAdopcion($publicante->ID_Usuario);
        $refugio = $this->refugioValidado();

        for ($i = 0; $i < 3; $i++)
        {
            $reportante = Particular::factory()->create();
            $this->withSession($this->sesion($reportante->ID_Usuario, 'Particular'))
                ->post('/reportar', ['publicacion' => $publicacion->ID_Publicacion, 'motivo' => 'Venta de animales'])
                ->assertSessionHas('exito');
            $this->withSession($this->sesion($reportante->ID_Usuario, 'Particular'))
                ->post('/reportar', ['usuario' => $refugio->ID_Usuario, 'motivo' => 'Datos de ubicación o contacto falsos'])
                ->assertSessionHas('exito');
        }

        $this->assertEquals('En revisión', $publicacion->fresh()->Estado_Moderacion);
        $this->assertEquals('Suspendido', $refugio->fresh()->Estado_Validacion);
    }

    // ---------- Refugio (adoptauy_refugio) ----------

    public function test_el_refugio_maneja_su_perfil_sus_cuentas_y_elige_al_adoptante()
    {
        $refugio = $this->refugioValidado();
        $sesion = $this->sesion($refugio->ID_Usuario, 'Refugio');
        $publicacion = $this->crearAdopcion($refugio->ID_Usuario);
        $adoptante = Particular::factory()->create();
        $solicitud = new SolicitudAdopcion(['ID_Publicacion' => $publicacion->ID_Publicacion]);
        $solicitud->ID_Usuario = $adoptante->ID_Usuario;
        $solicitud->save();

        $this->withSession($sesion)->get('/cuenta')->assertOk();
        $this->withSession($sesion)->get('/mi-refugio')->assertOk();
        $this->withSession($sesion)->put('/mi-refugio/ubicacion', ['latitud' => -34.9, 'longitud' => -56.16])->assertSessionHasNoErrors();
        $this->withSession($sesion)->post('/mi-refugio/cuentas', [
            'banco' => 'Banco de prueba', 'tipo_cuenta' => 'Caja de ahorro', 'numero_cuenta' => '000123', 'titular' => 'Titular de prueba',
        ])->assertSessionHasNoErrors();
        $this->withSession($sesion)->delete('/mi-refugio/cuentas/1')->assertSessionHas('exito');
        $this->assertEquals(0, CuentaBancaria::count());

        $this->withSession($sesion)->put('/perfil/datos', [
            'nombre_refugio' => 'Refugio de prueba Prado Norte', 'ubicacion' => 'Prado', 'contactos' => "098000111\n@refugio",
            'foto' => UploadedFile::fake()->image('refugio.jpg'),
        ])->assertSessionHas('exito');

        $this->withSession($sesion)->get('/solicitudes/recibidas')->assertOk()->assertSee($adoptante->Nombre)->assertSee($adoptante->Telefono);
        $this->withSession($sesion)->put('/solicitudes/' . $solicitud->ID_Solicitud . '/elegir')->assertSessionHas('exito');

        $this->assertEquals('Adoptada', $solicitud->fresh()->Estado_Solicitud);
        $this->assertEquals(1, Seguimiento::count());

        $otra = $this->crearAdopcion($refugio->ID_Usuario, 'Beagle');
        $this->withSession($sesion)->put('/adopciones/' . $otra->ID_Publicacion . '/archivar')->assertSessionHas('exito');
        $this->withSession($sesion)->put('/adopciones/' . $otra->ID_Publicacion . '/reactivar')->assertSessionHas('exito');
    }

    // ---------- Seguimiento: particular, refugio y sistema ----------

    public function test_seguimiento_completo_con_cada_usuario()
    {
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(10, 0));
        $refugio = $this->refugioValidado();
        $adoptante = Particular::factory()->create();
        $publicacion = $this->crearAdopcion($refugio->ID_Usuario);
        $solicitud = new SolicitudAdopcion(['ID_Publicacion' => $publicacion->ID_Publicacion]);
        $solicitud->ID_Usuario = $adoptante->ID_Usuario;
        $solicitud->save();

        $this->withSession($this->sesion($refugio->ID_Usuario, 'Refugio'))->put('/solicitudes/' . $solicitud->ID_Solicitud . '/elegir');
        $seguimiento = Seguimiento::first();

        // Día 15: la tarea programada corre con adoptauy_sistema
        $this->travelTo(now()->setDate(2026, 11, 16)->setTime(3, 0));
        DB::setDefaultConnection('sistema');
        Artisan::call('seguimientos:avisar');
        Artisan::call('publicaciones:archivar-vencidas');
        DB::setDefaultConnection('mysql');
        Mail::assertSent(\App\Mail\AvisoSeguimiento::class, 1);

        $this->withSession($this->sesion($adoptante->ID_Usuario, 'Particular'))->get('/seguimientos')->assertOk();
        $this->withSession($this->sesion($adoptante->ID_Usuario, 'Particular'))
            ->post('/seguimientos/' . $seguimiento->ID_Adopcion . '/foto', ['foto' => UploadedFile::fake()->image('gata.jpg')])
            ->assertSessionHas('exito');
        $this->withSession($this->sesion($refugio->ID_Usuario, 'Refugio'))
            ->put('/seguimientos/' . $seguimiento->ID_Adopcion . '/aprobar')->assertSessionHas('exito');

        $this->assertEquals('Aprobada', $seguimiento->fresh()->Estado_Evidencia);
    }

    // ---------- Administrador (adoptauy_admin) ----------

    public function test_el_administrador_modera_y_edita()
    {
        $admin = Administrador::factory()->create();
        $sesion = $this->sesion($admin->ID_Usuario, 'Administrador');
        $particular = Particular::factory()->create();
        $refugio = $this->refugioValidado();
        $publicacion = $this->crearAdopcion($particular->ID_Usuario);

        foreach (['/admin', '/admin/publicaciones?publicante=Particular', '/admin/refugios', '/admin/particulares', '/admin/reportes'] as $pantalla)
        {
            $this->withSession($sesion)->get($pantalla)->assertOk();
        }

        $this->withSession($sesion)->get('/publicaciones/' . $publicacion->ID_Publicacion . '/editar')->assertOk()
            ->assertSee('Las fotos las cambia solo quien publicó');
        $this->withSession($sesion)->put('/admin/particulares/' . $particular->ID_Usuario, [
            'nombre' => 'Corregido', 'apellido' => 'Apellido', 'telefono' => '099222333', 'ci' => '',
        ])->assertSessionHas('exito');
        $this->withSession($sesion)->put('/admin/refugios/' . $refugio->ID_Usuario . '/suspender')->assertSessionHas('exito');
        $this->withSession($sesion)->put('/admin/publicaciones/' . $publicacion->ID_Publicacion . '/archivar')->assertSessionHas('exito');

        $this->assertEquals('Corregido', $particular->fresh()->Nombre);
        $this->assertEquals('Suspendido', $refugio->fresh()->Estado_Validacion);
    }
}
