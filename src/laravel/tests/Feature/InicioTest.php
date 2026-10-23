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
 * Página de inicio pública (RF:0040, RF:0048). Caso CP-08.
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
    private function crearPublicacion($tipo, $estado, $texto, $moderacion = 'Visible', $vencimiento = null)
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
                'Nombre_Mascota' => $texto,
                'Caracteristicas' => 'Collar azul',
                'Fecha_Extravio' => now()->subDays(40)->toDateString(),
                'Zona_Extravio' => 'Centro',
                'Contacto' => '099 000 000',
            ]);
            $perdida->Estado_Perdida = $estado;
            $perdida->Fecha_Vencimiento = $vencimiento ?? now()->addDays(10)->toDateString();
            $perdida->save();
        }

        FotoAnimal::create(['ID_Publicacion' => $publicacion->ID_Publicacion, 'URL_Foto' => 'fotos-animales/prueba.jpg']);
    }

    public function test_cp08_solo_se_listan_las_en_adopcion_y_las_perdida()
    {
        $this->crearPublicacion('Adopción', 'En adopción', 'BarrioEnAdopcion');
        $this->crearPublicacion('Adopción', 'Adoptado', 'BarrioAdoptado');
        $this->crearPublicacion('Adopción', 'Archivado', 'BarrioArchivado');
        $this->crearPublicacion('Perdida', 'Perdida', 'NombrePerdida');
        $this->crearPublicacion('Perdida', 'Encontrada', 'NombreEncontrada');
        $this->crearPublicacion('Perdida', 'Archivada', 'NombreArchivada');

        $this->get('/')
            ->assertSee('BarrioEnAdopcion')
            ->assertDontSee('BarrioAdoptado')
            ->assertDontSee('BarrioArchivado')
            ->assertSee('NombrePerdida')
            ->assertDontSee('NombreEncontrada')
            ->assertDontSee('NombreArchivada');
    }

    public function test_no_se_listan_las_ocultas_por_moderacion_ni_las_perdidas_vencidas()
    {
        $this->crearPublicacion('Adopción', 'En adopción', 'BarrioEnRevision', 'En revisión');
        $this->crearPublicacion('Perdida', 'Perdida', 'NombreVencida', 'Visible', now()->subDay()->toDateString());

        $this->get('/')
            ->assertDontSee('BarrioEnRevision')
            ->assertDontSee('NombreVencida');
    }
}
