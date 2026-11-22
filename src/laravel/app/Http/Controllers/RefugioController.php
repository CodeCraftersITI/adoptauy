<?php

namespace App\Http\Controllers;

use App\Models\PublicacionAdopcion;
use App\Models\PublicacionPerdida;
use App\Models\Refugio;

// Sección pública de refugios (RF:0038, RF:0042, RF:0062)
class RefugioController extends Controller
{
    // GET /refugios
    public function index()
    {
        $refugios = Refugio::where('Estado_Validacion', 'Validado')
            ->orderBy('Nombre_Refugio')
            ->get();

        // Marcadores del mapa, que se le pasan al JavaScript como una lista
        $marcadores = [];

        foreach ($this->noRegistrados() as $refugio)
        {
            $marcadores[] = [
                'nombre' => $refugio['nombre'],
                'direccion' => $refugio['direccion'] ?? '',
                'latitud' => (float) $refugio['latitud'],
                'longitud' => (float) $refugio['longitud'],
                'perfil' => null,   // sin perfil: no está registrado en AdoptaUY
            ];
        }

        foreach ($refugios as $refugio)
        {
            if ($refugio->tieneUbicacion())
            {
                $marcadores[] = [
                    'nombre' => $refugio->Nombre_Refugio,
                    'direccion' => $refugio->Ubicacion,
                    'latitud' => (float) $refugio->Latitud,
                    'longitud' => (float) $refugio->Longitud,
                    'perfil' => '/refugios/' . $refugio->ID_Usuario,
                ];
            }
        }

        return view('refugios.index', [
            'refugios' => $refugios,
            'marcadores' => $marcadores,
        ]);
    }

    // GET /refugios/{id}
    public function show(string $id)
    {
        $refugio = Refugio::find($id);

        if (!$refugio || $refugio->Estado_Validacion != 'Validado')
        {
            abort(404);
        }

        // Sus mascotas publicadas: en adopción y avisos de perdidas y encontradas vigentes
        $adopciones = PublicacionAdopcion::where('Estado_Adopcion', 'En adopción')
            ->whereHas('publicacion', function ($publicacion) use ($id)
            {
                $publicacion->where('ID_Usuario', $id)->where('Estado_Moderacion', 'Visible');
            })
            ->orderBy('ID_Publicacion', 'desc')
            ->get();

        $avisos = PublicacionPerdida::where('Estado_Perdida', 'Vigente')
            ->where('Fecha_Vencimiento', '>', now()->toDateString())
            ->whereHas('publicacion', function ($publicacion) use ($id)
            {
                $publicacion->where('ID_Usuario', $id)->where('Estado_Moderacion', 'Visible');
            })
            ->orderBy('ID_Publicacion', 'desc')
            ->get();

        return view('refugios.show', [
            'refugio' => $refugio,
            'adopciones' => $adopciones,
            'avisos' => $avisos,
        ]);
    }

    // Lee el archivo de refugios no registrados; si no existe o está mal escrito, no muestra ninguno
    private function noRegistrados()
    {
        $ruta = resource_path('data/refugios-no-registrados.json');

        if (!file_exists($ruta))
        {
            return [];
        }

        $refugios = json_decode(file_get_contents($ruta), true);

        if (!is_array($refugios))
        {
            return [];
        }

        // Solo los que tienen nombre y coordenadas
        return array_filter($refugios, function ($refugio)
        {
            return isset($refugio['nombre'], $refugio['latitud'], $refugio['longitud']);
        });
    }
}
