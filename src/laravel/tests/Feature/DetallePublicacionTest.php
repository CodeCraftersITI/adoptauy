<?php

namespace Tests\Feature;

use App\Models\Administrador;
use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Detalle de una publicación: consulta pública de una mascota en adopción, perdida o
 * encontrada (RF:0040, RF:0048, RF:0065), sin datos personales del particular (RNF:0013).
 */
class DetallePublicacionTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdopcion($usuario, $estado = 'En adopción', $moderacion = 'Visible')
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Perro', 'Raza' => 'Mestizo', 'Sexo' => 'Macho']);
        $publicacion->ID_Usuario = $usuario->ID_Usuario;
        $publicacion->Tipo_Publicacion = 'Adopción';
        $publicacion->Estado_Moderacion = $moderacion;
        $publicacion->save();

        $adopcion = new PublicacionAdopcion([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Edad_Meses' => 8,
            'Tamaño' => 'Mediano',
            'Estado_Salud' => 'Vacunas al día',
            'Estado_Esterilizacion' => 'Esterilizado',
            'Ubicacion_Aproximada' => 'Pocitos',
        ]);
        $adopcion->Estado_Adopcion = $estado;
        $adopcion->save();

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/uno.jpg']);
        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/dos.jpg']);

        return $publicacion;
    }

    private function crearAviso($usuario, $tipo = 'Perdida', $nombre = 'Luna', $estado = 'Vigente')
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Gato', 'Raza' => 'Siamés', 'Sexo' => 'Hembra']);
        $publicacion->ID_Usuario = $usuario->ID_Usuario;
        $publicacion->Tipo_Publicacion = 'Perdida';
        $publicacion->save();

        $aviso = new PublicacionPerdida([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Tipo_Aviso' => $tipo,
            'Nombre_Mascota' => $nombre,
            'Caracteristicas' => 'Collar rojo',
            'Fecha_Suceso' => now()->subDays(2)->toDateString(),
            'Zona_Suceso' => 'Malvín',
            'Contacto' => '098765432',
        ]);
        $aviso->Estado_Perdida = $estado;
        $aviso->Fecha_Vencimiento = now()->addDays(28)->toDateString();
        $aviso->save();

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/tres.jpg']);

        return $publicacion;
    }

    public function test_un_invitado_ve_el_detalle_de_una_mascota_en_adopcion_con_todas_sus_fotos()
    {
        $particular = Particular::factory()->create();
        $publicacion = $this->crearAdopcion($particular->usuario);

        $this->get('/publicaciones/' . $publicacion->ID_Publicacion)
            ->assertOk()
            ->assertSee('Perro · Mestizo')
            ->assertSee('8 meses')
            ->assertSee('Pocitos')
            ->assertSee('Vacunas al día')
            ->assertSee('Esterilizado')
            ->assertSee('fotos-animales/uno.jpg')
            ->assertSee('fotos-animales/dos.jpg');
    }

    public function test_rnf0013_no_muestra_datos_personales_del_particular()
    {
        $particular = Particular::factory()->create();
        $publicacion = $this->crearAdopcion($particular->usuario);

        $this->get('/publicaciones/' . $publicacion->ID_Publicacion)
            ->assertSee('Un particular')
            ->assertDontSee($particular->CI)
            ->assertDontSee($particular->Telefono)
            ->assertDontSee($particular->usuario->Email)
            ->assertDontSee($particular->Apellido);
    }

    public function test_de_un_refugio_se_muestra_su_nombre()
    {
        $refugio = Refugio::factory()->validado()->create(['Nombre_Refugio' => 'Refugio de prueba Sur']);
        $publicacion = $this->crearAdopcion($refugio->usuario);

        $this->get('/publicaciones/' . $publicacion->ID_Publicacion)->assertSee('Refugio de prueba Sur');
    }

    public function test_el_detalle_de_una_perdida_muestra_el_contacto_para_llamar()
    {
        $publicacion = $this->crearAviso(Particular::factory()->create()->usuario);

        $this->get('/publicaciones/' . $publicacion->ID_Publicacion)
            ->assertOk()
            ->assertSee('Mascota perdida')
            ->assertSee('Luna')
            ->assertSee('Collar rojo')
            ->assertSee('Malvín')
            ->assertSee('098 765 432')
            ->assertSee('href="tel:098765432"', false);
    }

    public function test_una_encontrada_sin_nombre_se_muestra_como_sin_nombre()
    {
        $publicacion = $this->crearAviso(Particular::factory()->create()->usuario, 'Encontrada', null);

        $this->get('/publicaciones/' . $publicacion->ID_Publicacion)
            ->assertOk()
            ->assertSee('Mascota encontrada')
            ->assertSee('Sin nombre');
    }

    public function test_una_publicacion_que_no_es_publica_no_existe_para_un_visitante()
    {
        $usuario = Particular::factory()->create()->usuario;
        $noPublicas = [
            $this->crearAdopcion($usuario, 'Adoptado'),
            $this->crearAdopcion($usuario, 'Archivado'),
            $this->crearAdopcion($usuario, 'En adopción', 'En revisión'),
            $this->crearAviso($usuario, 'Perdida', 'Toby', 'Resuelta'),
            $this->crearAviso($usuario, 'Perdida', 'Toby', 'Archivada'),
        ];

        foreach ($noPublicas as $publicacion)
        {
            $this->get('/publicaciones/' . $publicacion->ID_Publicacion)->assertNotFound();
        }
    }

    public function test_el_publicante_y_el_administrador_ven_la_que_no_es_publica()
    {
        $particular = Particular::factory()->create();
        $publicacion = $this->crearAdopcion($particular->usuario, 'Archivado');
        $admin = Administrador::factory()->create();

        $this->withSession(['id_usuario' => $particular->ID_Usuario, 'tipo' => 'Particular', 'nombre' => 'Ana'])
            ->get('/publicaciones/' . $publicacion->ID_Publicacion)
            ->assertOk()
            ->assertSee('solo la ves vos');

        $this->withSession(['id_usuario' => $admin->ID_Usuario, 'tipo' => 'Administrador', 'nombre' => 'Administrador'])
            ->get('/publicaciones/' . $publicacion->ID_Publicacion)
            ->assertOk();
    }

    public function test_una_publicacion_que_no_existe_da_404()
    {
        $this->get('/publicaciones/9999')->assertNotFound();
    }

    public function test_las_tarjetas_del_inicio_llevan_al_detalle()
    {
        $usuario = Particular::factory()->create()->usuario;
        $adopcion = $this->crearAdopcion($usuario);
        $perdida = $this->crearAviso($usuario);

        $this->get('/')
            ->assertSee('href="/publicaciones/' . $adopcion->ID_Publicacion . '"', false)
            ->assertSee('href="/publicaciones/' . $perdida->ID_Publicacion . '"', false);
    }
}
