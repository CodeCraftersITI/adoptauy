<?php

namespace Tests\Feature;

use App\Models\FotoAnimal;
use App\Models\Particular;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Listados públicos: adopción con filtros (RF:0040, RF:0041, CP-12), perdidas (RF:0048)
 * y encontradas (RF:0065), con paginación de 12 por página.
 */
class ListadoTest extends TestCase
{
    use RefreshDatabase;

    private $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = Particular::factory()->create()->usuario;
    }

    // Crea una mascota en adopción; el barrio se usa como texto único para buscarla en la página
    private function adopcion($barrio, $cambios = [])
    {
        $datos = array_merge([
            'especie' => 'Perro', 'raza' => 'Mestizo', 'sexo' => 'Macho', 'meses' => 24,
            'estado' => 'En adopción', 'dias' => 0,
        ], $cambios);

        $publicacion = new PublicacionAnimal(['Especie' => $datos['especie'], 'Raza' => $datos['raza'], 'Sexo' => $datos['sexo']]);
        $publicacion->ID_Usuario = $this->usuario->ID_Usuario;
        $publicacion->Tipo_Publicacion = 'Adopción';
        $publicacion->Fecha_Creacion = now()->subDays($datos['dias']);
        $publicacion->save();

        $adopcion = new PublicacionAdopcion([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Edad_Meses' => $datos['meses'],
            'Tamaño' => 'Mediano',
            'Estado_Salud' => 'Sano',
            'Estado_Esterilizacion' => 'Esterilizado',
            'Ubicacion_Aproximada' => $barrio,
        ]);
        $adopcion->Estado_Adopcion = $datos['estado'];
        $adopcion->save();

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/x.jpg']);
    }

    private function aviso($tipo, $nombre)
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Gato', 'Raza' => 'Siamés', 'Sexo' => 'Hembra']);
        $publicacion->ID_Usuario = $this->usuario->ID_Usuario;
        $publicacion->Tipo_Publicacion = 'Perdida';
        $publicacion->save();

        $aviso = new PublicacionPerdida([
            'ID_Publicacion' => $publicacion->ID_Publicacion, 'Tipo_Aviso' => $tipo, 'Nombre_Mascota' => $nombre,
            'Caracteristicas' => 'Collar', 'Fecha_Suceso' => now()->toDateString(), 'Zona_Suceso' => 'Centro', 'Contacto' => '099000000',
        ]);
        $aviso->Fecha_Vencimiento = now()->addDays(30)->toDateString();
        $aviso->save();

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/x.jpg']);
    }

    public function test_el_listado_de_adopcion_es_publico_y_solo_muestra_las_en_adopcion()
    {
        $this->adopcion('BarrioEnAdopcion');
        $this->adopcion('BarrioAdoptado', ['estado' => 'Adoptado']);
        $this->adopcion('BarrioArchivado', ['estado' => 'Archivado']);

        $this->get('/adopcion')
            ->assertOk()
            ->assertSee('BarrioEnAdopcion')
            ->assertDontSee('BarrioAdoptado')
            ->assertDontSee('BarrioArchivado');
    }

    public function test_cp12_el_filtro_de_cachorros_muestra_11_meses_y_no_12()
    {
        $this->adopcion('BarrioOnceMeses', ['meses' => 11]);
        $this->adopcion('BarrioDoceMeses', ['meses' => 12]);

        $this->get('/adopcion?edad=cachorro')->assertSee('BarrioOnceMeses')->assertDontSee('BarrioDoceMeses');
        $this->get('/adopcion?edad=adulto')->assertSee('BarrioDoceMeses')->assertDontSee('BarrioOnceMeses');
    }

    public function test_cp12_especie_raza_sexo_y_fecha_se_combinan()
    {
        $this->adopcion('BarrioBuscado', ['especie' => 'Gato', 'raza' => 'Siamés', 'sexo' => 'Hembra', 'dias' => 2]);
        $this->adopcion('BarrioOtraEspecie', ['especie' => 'Perro', 'raza' => 'Siamés', 'sexo' => 'Hembra', 'dias' => 2]);
        $this->adopcion('BarrioOtraRaza', ['especie' => 'Gato', 'raza' => 'Persa', 'sexo' => 'Hembra', 'dias' => 2]);
        $this->adopcion('BarrioOtroSexo', ['especie' => 'Gato', 'raza' => 'Siamés', 'sexo' => 'Macho', 'dias' => 2]);
        $this->adopcion('BarrioVieja', ['especie' => 'Gato', 'raza' => 'Siamés', 'sexo' => 'Hembra', 'dias' => 20]);

        $this->get('/adopcion?especie=Gato&raza=siam&sexo=Hembra&fecha=7')
            ->assertSee('BarrioBuscado')
            ->assertDontSee('BarrioOtraEspecie')
            ->assertDontSee('BarrioOtraRaza')
            ->assertDontSee('BarrioOtroSexo')
            ->assertDontSee('BarrioVieja');
    }

    public function test_sin_resultados_muestra_un_mensaje()
    {
        $this->adopcion('BarrioPerro');

        $this->get('/adopcion?especie=Gato')->assertSee('No hay mascotas en adopción que cumplan esos filtros.');
    }

    public function test_muestra_12_por_pagina_y_la_pagina_2_conserva_los_filtros()
    {
        for ($i = 1; $i <= 13; $i++)
        {
            $this->adopcion('Barrio' . $i);
        }

        $primera = $this->get('/adopcion?especie=Perro');
        $primera->assertSee('Página 1 de 2')->assertSee('Barrio13')->assertDontSee('Barrio1<', false);
        $primera->assertSee('especie=Perro&amp;page=2', false);

        $this->get('/adopcion?especie=Perro&page=2')->assertSee('Barrio1');
    }

    public function test_perdidas_y_encontradas_tienen_listados_separados()
    {
        $this->aviso('Perdida', 'NombrePerdida');
        $this->aviso('Encontrada', 'NombreEncontrada');

        $this->get('/perdidas')->assertOk()->assertSee('NombrePerdida')->assertDontSee('NombreEncontrada');
        $this->get('/encontradas')->assertOk()->assertSee('NombreEncontrada')->assertDontSee('NombrePerdida');
    }

    public function test_el_menu_lleva_a_los_listados_y_marca_la_pagina_actual()
    {
        $this->get('/perdidas')
            ->assertSee('href="/adopcion"', false)
            ->assertSee('href="/encontradas"', false)
            ->assertSee('nav-enlace-activo" href="/perdidas"', false);
    }
}
