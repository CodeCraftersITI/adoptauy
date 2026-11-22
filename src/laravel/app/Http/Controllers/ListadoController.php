<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAdopcion;
use App\Models\PublicacionPerdida;
use Illuminate\Http\Request;

// Listados públicos, sin sesión (RF:0040, RF:0041, RF:0021, RF:0048, RF:0065)
class ListadoController extends Controller
{
    private const POR_PAGINA = 12;

    // GET /adopcion?especie=&sexo=&raza=&edad=&fecha=
    public function adopcion(Request $request)
    {
        // Solo las En adopción (RF:0040)
        $consulta = PublicacionAdopcion::where('Estado_Adopcion', 'En adopción')
            ->whereHas('publicacion', function ($publicacion) use ($request)
            {
                $publicacion->where('Estado_Moderacion', 'Visible');

                if ($request->filled('especie'))
                {
                    $publicacion->where('Especie', $request->input('especie'));
                }

                if ($request->filled('sexo'))
                {
                    $publicacion->where('Sexo', $request->input('sexo'));
                }

                // Raza: alcanza con que contenga lo escrito ("lab" encuentra "Labrador")
                if ($request->filled('raza'))
                {
                    $publicacion->where('Raza', 'like', '%' . $request->input('raza') . '%');
                }

                // Fecha de publicación: últimos 7 o 30 días
                if ($request->input('fecha') == '7' || $request->input('fecha') == '30')
                {
                    $publicacion->where('Fecha_Creacion', '>=', now()->subDays($request->input('fecha')));
                }
            });

        // Edad (dato de Publicacion_Adopcion): cachorro = menos de 12 meses (D63)
        if ($request->input('edad') == 'cachorro')
        {
            $consulta->where('Edad_Meses', '<', 12);
        }
        if ($request->input('edad') == 'adulto')
        {
            $consulta->where('Edad_Meses', '>=', 12);
        }

        // withQueryString(): los enlaces de página siguiente/anterior conservan los filtros
        $adopciones = $consulta->orderBy('ID_Publicacion', 'desc')
            ->paginate(self::POR_PAGINA)
            ->withQueryString();

        return view('listados.adopcion', ['adopciones' => $adopciones]);
    }

    // GET /perdidas
    public function perdidas()
    {
        return $this->avisos(PublicacionPerdida::PERDIDA, 'Mascotas perdidas', 'Su familia las está buscando. Si viste alguna, comunicate con el contacto.');
    }

    // GET /encontradas
    public function encontradas()
    {
        return $this->avisos(PublicacionPerdida::ENCONTRADA, 'Mascotas encontradas', 'Alguien las encontró y busca a su familia. ¿Reconocés a alguna?');
    }

    // Perdidas y encontradas usan la misma vista: cambian el tipo de aviso y los textos
    private function avisos($tipo, $titulo, $descripcion)
    {
        $avisos = PublicacionPerdida::vigentes($tipo)
            ->orderBy('ID_Publicacion', 'desc')
            ->paginate(self::POR_PAGINA);

        return view('listados.avisos', [
            'avisos' => $avisos,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
        ]);
    }
}
