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
 * Página de inicio pública (RF:0040, RF:0048, RF:0065). Casos CP-08 y CP-48.
 */
class InicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_ve_la_pagina_de_inicio_con_el_layout()
    {
        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertSee('Adoptar cambia dos vidas.');
        $respuesta->assertSee('AdoptaUY · CodeCrafters · ITI CETP-UTU');   // viene del partial pie
        $respuesta->assertSee('Ingresar');                                  // viene del partial acciones-usuario
    }

    public function test_la_pagina_sobre_nosotros_muestra_al_equipo()
    {
        $this->get('/nosotros')
            ->assertOk()
            ->assertSee('El equipo: CodeCrafters')
            ->assertSee('Iñaki Pérez');
    }

    public function test_el_menu_no_tiene_enlaces_vacios()
    {
        $this->get('/')->assertDontSee('href="#"', false);
    }

    public function test_con_sesion_iniciada_se_ve_cerrar_sesion()
    {
        $this->withSession(['id_usuario' => 1, 'tipo' => 'Particular', 'nombre' => 'Ana'])
            ->get('/')
            ->assertSee('Cerrar sesión')
            ->assertSee('Mi cuenta');
    }

    // Crea una publicación con su foto directamente en la base, en el estado pedido
    private function crearPublicacion($tipo, $estado, $texto, $moderacion = 'Visible', $vencimiento = null, $aviso = 'Perdida')
    {
        $publicacion = new PublicacionAnimal(['Especie' => 'Perro', 'Raza' => 'mestizo', 'Sexo' => 'Macho']);
        $publicacion->ID_Usuario = Particular::factory()->create()->ID_Usuario;
        $publicacion->Tipo_Publicacion = $tipo;
        $publicacion->Estado_Moderacion = $moderacion;
        $publicacion->save();

        if ($tipo == 'Adopción')
        {
            $adopcion = new PublicacionAdopcion([
                'ID_Publicacion' => $publicacion->ID_Publicacion,
                'Edad_Meses' => 10,
                'Tamaño' => 'Mediano',
                'Estado_Salud' => 'Sano',
                'Estado_Esterilizacion' => 'Esterilizado',
                'Ubicacion_Aproximada' => $texto,
            ]);
            $adopcion->Estado_Adopcion = $estado;
            $adopcion->save();
        }
        else
        {
            $perdida = new PublicacionPerdida([
                'ID_Publicacion' => $publicacion->ID_Publicacion,
                'Tipo_Aviso' => $aviso,
                'Nombre_Mascota' => $texto,
                'Caracteristicas' => 'Collar azul',
                'Fecha_Suceso' => now()->subDays(40)->toDateString(),
                'Zona_Suceso' => 'Centro',
                'Contacto' => '099 000 000',
            ]);
            $perdida->Estado_Perdida = $estado;
            $perdida->Fecha_Vencimiento = $vencimiento ?? now()->addDays(10)->toDateString();
            $perdida->save();
        }

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/prueba.jpg']);
    }

    public function test_cp08_solo_se_listan_las_en_adopcion_y_las_perdidas_vigentes()
    {
        $this->crearPublicacion('Adopción', 'En adopción', 'BarrioEnAdopcion');
        $this->crearPublicacion('Adopción', 'Adoptado', 'BarrioAdoptado');
        $this->crearPublicacion('Adopción', 'Archivado', 'BarrioArchivado');
        $this->crearPublicacion('Perdida', 'Vigente', 'NombrePerdida');
        $this->crearPublicacion('Perdida', 'Resuelta', 'NombreResuelta');
        $this->crearPublicacion('Perdida', 'Archivada', 'NombreArchivada');

        $this->get('/')
            ->assertSee('BarrioEnAdopcion')
            ->assertDontSee('BarrioAdoptado')
            ->assertDontSee('BarrioArchivado')
            ->assertSee('NombrePerdida')
            ->assertDontSee('NombreResuelta')
            ->assertDontSee('NombreArchivada');
    }

    public function test_cp48_las_encontradas_vigentes_se_listan_en_su_propio_apartado()
    {
        $this->crearPublicacion('Perdida', 'Vigente', 'NombrePerdida');
        $this->crearPublicacion('Perdida', 'Vigente', 'NombreEncontrada', 'Visible', null, 'Encontrada');
        $this->crearPublicacion('Perdida', 'Resuelta', 'NombreEncontradaResuelta', 'Visible', null, 'Encontrada');
        $this->crearPublicacion('Perdida', 'Vigente', 'NombreEncontradaVencida', 'Visible', now()->subDay()->toDateString(), 'Encontrada');
        $this->crearPublicacion('Perdida', 'Vigente', 'NombreEncontradaEnRevision', 'En revisión', null, 'Encontrada');

        $respuesta = $this->get('/')->assertOk();

        // Cada una aparece en su sección: las perdidas antes del título «Mascotas encontradas», las encontradas después
        $respuesta->assertSeeInOrder(['Mascotas perdidas', 'NombrePerdida', 'Mascotas encontradas', 'NombreEncontrada']);
        $respuesta->assertDontSee('NombreEncontradaResuelta')
            ->assertDontSee('NombreEncontradaVencida')
            ->assertDontSee('NombreEncontradaEnRevision');
    }

    public function test_no_se_listan_las_ocultas_por_moderacion_ni_las_perdidas_vencidas()
    {
        $this->crearPublicacion('Adopción', 'En adopción', 'BarrioEnRevision', 'En revisión');
        $this->crearPublicacion('Perdida', 'Vigente', 'NombreVencida', 'Visible', now()->subDay()->toDateString());

        $this->get('/')
            ->assertDontSee('BarrioEnRevision')
            ->assertDontSee('NombreVencida');
    }
}
