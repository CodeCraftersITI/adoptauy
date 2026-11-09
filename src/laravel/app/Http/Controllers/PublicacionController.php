<?php

namespace App\Http\Controllers;

use App\Models\FotoAnimal;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Alta de publicaciones: mascota en adopción (RF:0017, RF:0024) y mascota perdida (RF:0046).
 * Las rutas están protegidas con rol:Particular,Refugio: solo publican los usuarios con
 * sesión iniciada, y un refugio solo puede iniciar sesión si está Validado (RNE04, RNE16).
 */
class PublicacionController extends Controller
{
    // Mensajes de error compartidos por los dos formularios
    private const MENSAJES = [
        'especie.required' => 'Elegí la especie.',
        'especie.in' => 'La especie tiene que ser Perro o Gato.',
        'raza.required' => 'Ingresá la raza (o "mestizo").',
        'raza.max' => 'La raza puede tener hasta 50 caracteres.',
        'sexo.required' => 'Elegí el sexo.',
        'sexo.in' => 'El sexo tiene que ser Macho o Hembra.',
        'fotos.required' => 'Subí al menos una foto.',
        'fotos.min' => 'Subí al menos una foto.',
        'fotos.max' => 'Podés subir hasta :max fotos.',
        'fotos.*.mimes' => 'Cada foto tiene que ser JPG, PNG o WEBP.',
        'fotos.*.max' => 'Cada foto puede pesar hasta 8 MB.',
        'fotos.*.file' => 'No se pudo subir una de las fotos.',
    ];

    // ---------- Mascota en adopción ----------

    // GET /publicar/adopcion
    public function crearAdopcion()
    {
        return view('publicaciones.crear-adopcion');
    }

    // POST /publicar/adopcion
    public function guardarAdopcion(Request $request)
    {
        // Campos obligatorios de 13.2.2 y entre 1 y 6 fotos de hasta 8 MB (RNE01)
        $request->validate([
            'especie' => 'required|in:Perro,Gato',
            'raza' => 'required|max:50',
            'sexo' => 'required|in:Macho,Hembra',
            'edad_anios' => 'required|integer|min:0|max:30',
            'edad_meses' => 'required|integer|min:0|max:11',
            'tamanio' => 'required|in:Pequeño,Mediano,Grande',
            'salud' => 'required|max:255',
            'esterilizacion' => 'required|in:Esterilizado,No esterilizado',
            'ubicacion' => 'required|max:100',
            'fotos' => 'required|array|min:1|max:6',
            'fotos.*' => 'file|mimes:jpg,jpeg,png,webp|max:8192',
        ], self::MENSAJES + [
            'edad_anios.required' => 'Ingresá los años (0 si tiene menos de un año).',
            'edad_anios.integer' => 'Los años tienen que ser un número.',
            'edad_anios.min' => 'Los años no pueden ser negativos.',
            'edad_anios.max' => 'Revisá los años: el máximo es 30.',
            'edad_meses.required' => 'Ingresá los meses (0 a 11).',
            'edad_meses.integer' => 'Los meses tienen que ser un número.',
            'edad_meses.min' => 'Los meses van de 0 a 11.',
            'edad_meses.max' => 'Los meses van de 0 a 11.',
            'tamanio.required' => 'Elegí el tamaño.',
            'tamanio.in' => 'El tamaño tiene que ser Pequeño, Mediano o Grande.',
            'salud.required' => 'Contá el estado de salud.',
            'salud.max' => 'El estado de salud puede tener hasta 255 caracteres.',
            'esterilizacion.required' => 'Indicá si está esterilizado.',
            'esterilizacion.in' => 'Elegí Esterilizado o No esterilizado.',
            'ubicacion.required' => 'Ingresá la ubicación aproximada.',
            'ubicacion.max' => 'La ubicación puede tener hasta 100 caracteres.',
        ]);

        DB::transaction(function () use ($request)
        {
            $publicacion = $this->crearPublicacion($request, 'Adopción');

            // La edad se guarda en meses (RNE12): 2 años y 3 meses = 27
            PublicacionAdopcion::create([
                'ID_Publicacion' => $publicacion->ID_Publicacion,
                'Edad_Meses' => $request->input('edad_anios') * 12 + $request->input('edad_meses'),
                'Tamaño' => $request->input('tamanio'),
                'Estado_Salud' => $request->input('salud'),
                'Estado_Esterilizacion' => $request->input('esterilizacion'),
                'Ubicacion_Aproximada' => $request->input('ubicacion'),
            ]);

            $this->guardarFotos($request, $publicacion);
        });

        return redirect('/cuenta')->with('exito', 'Tu publicación ya está en adopción y la puede ver cualquier visitante.');
    }

    // ---------- Mascota perdida ----------

    // GET /publicar/perdida
    public function crearPerdida()
    {
        return view('publicaciones.crear-perdida');
    }

    // POST /publicar/perdida
    public function guardarPerdida(Request $request)
    {
        // Campos de 13.2.2 para mascotas perdidas y entre 1 y 3 fotos (RNE01)
        $request->validate([
            'nombre_mascota' => 'required|max:50',
            'especie' => 'required|in:Perro,Gato',
            'raza' => 'required|max:50',
            'sexo' => 'required|in:Macho,Hembra',
            'caracteristicas' => 'required|max:1000',
            'fecha_extravio' => 'required|date|before_or_equal:today',
            'zona' => 'required|max:100',
            'contacto' => 'required|max:100',
            'fotos' => 'required|array|min:1|max:3',
            'fotos.*' => 'file|mimes:jpg,jpeg,png,webp|max:8192',
        ], self::MENSAJES + [
            'nombre_mascota.required' => 'Ingresá el nombre de la mascota.',
            'nombre_mascota.max' => 'El nombre puede tener hasta 50 caracteres.',
            'caracteristicas.required' => 'Contá cómo es (color, collar, señas particulares).',
            'caracteristicas.max' => 'Las características pueden tener hasta 1000 caracteres.',
            'fecha_extravio.required' => 'Ingresá la fecha en que se perdió.',
            'fecha_extravio.date' => 'La fecha no es válida.',
            'fecha_extravio.before_or_equal' => 'La fecha del extravío no puede ser futura.',
            'zona.required' => 'Ingresá la zona donde se perdió.',
            'zona.max' => 'La zona puede tener hasta 100 caracteres.',
            'contacto.required' => 'Ingresá un teléfono o correo de contacto.',
            'contacto.max' => 'El contacto puede tener hasta 100 caracteres.',
        ]);

        DB::transaction(function () use ($request)
        {
            $publicacion = $this->crearPublicacion($request, 'Perdida');

            // Estado_Perdida y Fecha_Vencimiento no están en $fillable: se asignan a mano
            $perdida = new PublicacionPerdida([
                'ID_Publicacion' => $publicacion->ID_Publicacion,
                'Nombre_Mascota' => $request->input('nombre_mascota'),
                'Caracteristicas' => $request->input('caracteristicas'),
                'Fecha_Extravio' => $request->input('fecha_extravio'),
                'Zona_Extravio' => $request->input('zona'),
                'Contacto' => $request->input('contacto'),
            ]);
            $perdida->Fecha_Vencimiento = now()->addDays(30)->toDateString();   // RNE17
            $perdida->save();

            $this->guardarFotos($request, $publicacion);
        });

        return redirect('/cuenta')->with('exito', 'Publicaste a ' . $request->input('nombre_mascota')
            . '. La publicación vence en 30 días y la podés renovar.');
    }

    // ---------- Pasos comunes a los dos tipos ----------

    // Crea la fila de Publicacion_Animal con los datos comunes
    private function crearPublicacion(Request $request, $tipo)
    {
        $publicacion = new PublicacionAnimal([
            'Especie' => $request->input('especie'),
            'Raza' => $request->input('raza'),
            'Sexo' => $request->input('sexo'),
        ]);
        $publicacion->ID_Usuario = session('id_usuario');   // el dueño es quien inició sesión (RNE03)
        $publicacion->Tipo_Publicacion = $tipo;
        $publicacion->save();

        return $publicacion;
    }

    // Guarda cada foto en storage/app/public/fotos-animales y su ruta en Foto_Animal
    private function guardarFotos(Request $request, $publicacion)
    {
        foreach ($request->file('fotos') as $foto)
        {
            FotoAnimal::create([
                'ID_Publicacion' => $publicacion->ID_Publicacion,
                'URL_Foto' => $foto->store('fotos-animales', 'public'),
            ]);
        }
    }
}
