<?php

namespace Tests\Feature;

use App\Mail\SolicitudRefugioRecibida;
use App\Models\Refugio;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Solicitud de registro de refugio (RF:0034, RF:0063). Caso CP-17.
 * Storage::fake() y Mail::fake() evitan guardar archivos y mandar correos de verdad.
 */
class RegistroRefugioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake();
        Mail::fake();
    }

    private function datos($cambios = [])
    {
        return array_merge([
            'nombre_refugio' => 'Refugio de prueba',
            'contacto' => '099 000 000',
            'ubicacion' => 'Montevideo',
            'certificado' => UploadedFile::fake()->create('certificado.pdf', 500, 'application/pdf'),
            'comprobante' => UploadedFile::fake()->image('comprobante.jpg'),
            'email' => 'refugio@prueba.uy',
            'password' => 'clave-segura',
        ], $cambios);
    }

    public function test_el_formulario_se_muestra()
    {
        $this->get('/registro-refugio')->assertOk()->assertSee('Enviar solicitud');
    }

    public function test_cp17_la_solicitud_queda_pendiente_con_sus_documentos_y_su_contacto()
    {
        $this->post('/registro-refugio', $this->datos())->assertRedirect('/');

        $usuario = Usuario::where('Email', 'refugio@prueba.uy')->first();
        $this->assertEquals('Refugio', $usuario->Tipo_Usuario);

        $refugio = Refugio::find($usuario->ID_Usuario);
        $this->assertEquals('Pendiente de validación', $refugio->Estado_Validacion);
        Storage::assertExists($refugio->Certificado_Personeria);
        Storage::assertExists($refugio->Comprobante_Domicilio);

        $this->assertDatabaseHas('Refugio_Datos_Contacto', [
            'ID_Usuario' => $usuario->ID_Usuario,
            'Dato_Contacto' => '099 000 000',
        ]);
    }

    public function test_cp17_llega_un_unico_correo_de_recepcion()
    {
        $this->post('/registro-refugio', $this->datos());

        Mail::assertSent(SolicitudRefugioRecibida::class, 1);
        Mail::assertSent(SolicitudRefugioRecibida::class, function ($correo)
        {
            return $correo->hasTo('refugio@prueba.uy');
        });
    }

    public function test_rechaza_documentos_en_otro_formato()
    {
        $this->post('/registro-refugio', $this->datos([
            'certificado' => UploadedFile::fake()->create('certificado.docx', 100),
        ]))->assertSessionHasErrors('certificado');

        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_rechaza_documentos_de_mas_de_8_mb()
    {
        $this->post('/registro-refugio', $this->datos([
            'comprobante' => UploadedFile::fake()->create('comprobante.pdf', 8193, 'application/pdf'),
        ]))->assertSessionHasErrors('comprobante');

        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_rechaza_sin_documentos_ni_nombre()
    {
        $datos = $this->datos(['nombre_refugio' => '']);
        unset($datos['certificado'], $datos['comprobante']);

        $this->post('/registro-refugio', $datos)->assertSessionHasErrors(['nombre_refugio', 'certificado', 'comprobante']);
        $this->assertDatabaseCount('Usuario', 0);
    }

    public function test_cp18_un_refugio_pendiente_no_puede_iniciar_sesion()
    {
        $this->post('/registro-refugio', $this->datos());

        $this->post('/ingresar', ['email' => 'refugio@prueba.uy', 'password' => 'clave-segura'])
            ->assertSessionHas('error', 'El correo o la contraseña no son correctos.');

        $this->assertNull(session('id_usuario'));
    }
}
