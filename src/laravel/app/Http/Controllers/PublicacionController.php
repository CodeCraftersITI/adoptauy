<?php

namespace App\Http\Controllers;

use App\Models\FotoAnimal;
use App\Models\PublicacionAdopcion;
use App\Models\PublicacionAnimal;
use App\Models\PublicacionPerdida;
use App\Models\Seguimiento;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Alta de publicaciones con un único formulario (corrección del docente: código DRY).
 * El usuario elige qué publica y el formulario muestra los campos de ese tipo:
 *  - Adopción: mascota en adopción (RF:0017, RF:0024), entre 1 y 6 fotos.
 *  - Perdida: mascota perdida (RF:0046), entre 1 y 3 fotos.
 *  - Encontrada: mascota ajena encontrada (RF:0064), entre 1 y 3 fotos.
 * Editar (RF:0019 Particular, RF:0026 Refugio, RF:0003 Administrador) usa el mismo formulario
 * y las mismas validaciones; el tipo no se cambia y las fotos solo se reemplazan si se suben nuevas.
 * Las rutas están protegidas con rol:Particular,Refugio: solo publican los usuarios con
 * sesión iniciada, y un refugio solo puede iniciar sesión si está Validado (RNE04, RNE16).
 */
class PublicacionController extends Controller
{
    private const TIPOS = ['Adopción', 'Perdida', 'Encontrada'];

    // Mensajes de error de los campos que tienen todos los tipos
    private const MENSAJES = [
        'tipo.required' => 'Elegí qué querés publicar.',
        'tipo.in' => 'Elegí qué querés publicar.',
        'especie.required' => 'Elegí la especie.',
        'especie.in' => 'La especie tiene que ser Perro o Gato.',
        'raza.required' => 'Ingresá la raza (o "mestizo").',
        'raza.min' => 'La raza debe tener al menos 2 caracteres.',
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

    // GET /publicar?tipo=Perdida
    // El tipo de la URL solo elige cuál aparece marcado al abrir (por ejemplo, desde "Publicar perdida")
    public function crear(Request $request)
    {
        if ($this->cuentaRestringida())
        {
            return redirect('/seguimientos')->with('error', Seguimiento::MENSAJE_RESTRINGIDA);
        }

        $tipo = in_array($request->input('tipo'), self::TIPOS) ? $request->input('tipo') : 'Adopción';

        return view('publicaciones.crear', ['tipo' => $tipo]);
    }

    // POST /publicar
    public function guardar(Request $request)
    {
        if ($this->cuentaRestringida())
        {
            return redirect('/seguimientos')->with('error', Seguimiento::MENSAJE_RESTRINGIDA);
        }

        $tipo = $request->input('tipo');

        // El contacto es un teléfono (RNE12): se guarda solo con números, como en el registro
        $request->merge(['contacto' => str_replace(' ', '', $request->input('contacto') ?? '')]);

        $request->validate($this->reglas($tipo), self::MENSAJES + $this->mensajes($tipo));

        DB::transaction(function () use ($request, $tipo)
        {
            if ($tipo == 'Adopción')
            {
                $publicacion = $this->crearPublicacion($request, 'Adopción');
                $this->guardarAdopcion($request, $publicacion);
            }
            else
            {
                // Perdida y Encontrada comparten tabla (Publicacion_Perdida, RNE26)
                $publicacion = $this->crearPublicacion($request, 'Perdida');
                $this->guardarAviso($request, $publicacion, $tipo);
            }

            $this->guardarFotos($request, $publicacion);
        });

        if ($tipo == 'Adopción')
        {
            return redirect('/cuenta')->with('exito', 'Tu publicación ya está en adopción y la puede ver cualquier visitante.');
        }

        return redirect('/cuenta')->with('exito', 'Publicaste la mascota ' . ($tipo == 'Perdida' ? 'perdida' : 'encontrada')
            . '. La publicación vence en 30 días y la podés renovar.');
    }

    // GET /publicaciones/{id}/editar: el mismo formulario, con los datos cargados
    public function editar(string $id)
    {
        $publicacion = $this->buscarEditable($id);

        if (!$publicacion->sePuedeEditar())
        {
            return redirect('/publicaciones/' . $id)->with('error', 'Una publicación adoptada o resuelta ya no se puede editar.');
        }

        return view('publicaciones.crear', [
            'tipo' => $publicacion->tipoVisible(),
            'publicacion' => $publicacion,
        ]);
    }

    // PUT /publicaciones/{id}: guarda los cambios (RF:0019, RF:0026, RF:0003)
    public function actualizar(Request $request, string $id)
    {
        $publicacion = $this->buscarEditable($id);

        if (!$publicacion->sePuedeEditar())
        {
            return redirect('/publicaciones/' . $id)->with('error', 'Una publicación adoptada o resuelta ya no se puede editar.');
        }

        // El tipo no se cambia: sale de la publicación, no del formulario
        $tipo = $publicacion->tipoVisible();
        $request->merge([
            'tipo' => $tipo,
            'contacto' => str_replace(' ', '', $request->input('contacto') ?? ''),
        ]);

        $request->validate($this->reglas($tipo, true), self::MENSAJES + $this->mensajes($tipo));

        DB::transaction(function () use ($request, $publicacion)
        {
            $publicacion->update([
                'Especie' => $request->input('especie'),
                'Raza' => $request->input('raza'),
                'Sexo' => $request->input('sexo'),
            ]);

            if ($publicacion->Tipo_Publicacion == 'Adopción')
            {
                $publicacion->adopcion->update($this->datosAdopcion($request));
            }
            else
            {
                $publicacion->perdida->update($this->datosAviso($request));
            }

            // Si subió fotos nuevas, reemplazan a las anteriores; si no, quedan las que había
            if ($request->hasFile('fotos'))
            {
                foreach ($publicacion->fotos as $foto)
                {
                    Storage::disk('public')->delete($foto->URL_Foto);
                    $foto->delete();
                }

                $this->guardarFotos($request, $publicacion);
            }
        });

        return redirect('/publicaciones/' . $id)->with('exito', 'Guardaste los cambios de la publicación.');
    }

    // Busca la publicación; la edita su publicante o el Administrador (RF:0003). Si no, 403
    private function buscarEditable($id)
    {
        $publicacion = PublicacionAnimal::find($id);

        if (!$publicacion)
        {
            abort(404);
        }

        if ($publicacion->ID_Usuario != session('id_usuario') && session('tipo') != 'Administrador')
        {
            abort(403, 'No podés editar una publicación de otra persona.');
        }

        return $publicacion;
    }

    // RF:0058: con un seguimiento incumplido no se puede publicar de ningún tipo
    private function cuentaRestringida()
    {
        return Usuario::estaRestringido(session('id_usuario'));
    }

    // Reglas de validación: las comunes más las del tipo elegido (13.2.2, RNE01).
    // Al editar ($editando), las fotos son opcionales: si no se suben, quedan las que había
    private function reglas($tipo, $editando = false)
    {
        $fotos = $editando ? 'nullable|array|min:1' : 'required|array|min:1';

        $reglas = [
            'tipo' => 'required|in:Adopción,Perdida,Encontrada',
            'especie' => 'required|in:Perro,Gato',
            'raza' => 'required|min:2|max:50',
            'sexo' => 'required|in:Macho,Hembra',
            'fotos.*' => 'file|mimes:jpg,jpeg,png,webp|max:8192',
        ];

        if ($tipo == 'Adopción')
        {
            return $reglas + [
                // RNE12: la edad no tiene máximo de negocio; 5460 es el tope técnico de Edad_Meses
                // (smallint sin signo: 5460 años), para que un valor absurdo dé un error y no un error 500 (RNF:0010)
                'edad' => 'required|integer|min:0|max:5460',
                'edad_unidad' => 'required|in:meses,años',
                'tamanio' => 'required|in:Pequeño,Mediano,Grande',
                'salud' => 'required|max:255',
                'esterilizacion' => 'required|in:Esterilizado,No esterilizado',
                'ubicacion' => 'required|min:3|max:100',
                'fotos' => $fotos . '|max:6',
            ];
        }

        // Perdida o Encontrada: el nombre es obligatorio solo en la perdida
        return $reglas + [
            'nombre_mascota' => ($tipo == 'Encontrada' ? 'nullable' : 'required') . '|max:50',
            'caracteristicas' => 'required|max:1000',
            'fecha' => 'required|date|before_or_equal:today',
            'zona' => 'required|min:3|max:100',
            'contacto' => 'required|regex:/^[0-9]{8,9}$/',
            'fotos' => $fotos . '|max:3',
        ];
    }

    // Mensajes de los campos propios de cada tipo
    private function mensajes($tipo)
    {
        if ($tipo == 'Adopción')
        {
            return [
                'edad.required' => 'Ingresá la edad estimada.',
                'edad.integer' => 'La edad tiene que ser un número.',
                'edad.min' => 'La edad no puede ser negativa.',
                'edad.max' => 'Revisá la edad.',
                'edad_unidad.required' => 'Elegí si la edad está en meses o en años.',
                'edad_unidad.in' => 'Elegí si la edad está en meses o en años.',
                'tamanio.required' => 'Elegí el tamaño.',
                'tamanio.in' => 'El tamaño tiene que ser Pequeño, Mediano o Grande.',
                'salud.required' => 'Contá el estado de salud.',
                'salud.max' => 'El estado de salud puede tener hasta 255 caracteres.',
                'esterilizacion.required' => 'Indicá si está castrado.',
                'esterilizacion.in' => 'Elegí Castrado o No castrado.',
                'ubicacion.required' => 'Ingresá la ubicación aproximada.',
                'ubicacion.min' => 'La ubicación debe tener al menos 3 caracteres.',
                'ubicacion.max' => 'La ubicación puede tener hasta 100 caracteres.',
            ];
        }

        $encontrada = $tipo == 'Encontrada';

        return [
            'nombre_mascota.required' => 'Ingresá el nombre de la mascota.',
            'nombre_mascota.max' => 'El nombre puede tener hasta 50 caracteres.',
            'caracteristicas.required' => 'Contá cómo es (color, collar, señas particulares).',
            'caracteristicas.max' => 'Las características pueden tener hasta 1000 caracteres.',
            'fecha.required' => $encontrada ? 'Ingresá la fecha en que la encontraste.' : 'Ingresá la fecha en que se perdió.',
            'fecha.date' => 'La fecha no es válida.',
            'fecha.before_or_equal' => 'La fecha ' . ($encontrada ? 'del hallazgo' : 'del extravío') . ' no puede ser futura.',
            'zona.required' => $encontrada ? 'Ingresá la zona donde la encontraste.' : 'Ingresá la zona donde se perdió.',
            'zona.min' => 'La zona debe tener al menos 3 caracteres.',
            'zona.max' => 'La zona puede tener hasta 100 caracteres.',
            'contacto.required' => 'Ingresá un teléfono de contacto.',
            'contacto.regex' => 'El teléfono debe tener 8 o 9 números.',
        ];
    }

    // Crea la fila de Publicacion_Animal con los datos comunes
    private function crearPublicacion(Request $request, $tipoPublicacion)
    {
        $publicacion = new PublicacionAnimal([
            'Especie' => $request->input('especie'),
            'Raza' => $request->input('raza'),
            'Sexo' => $request->input('sexo'),
        ]);
        $publicacion->ID_Usuario = session('id_usuario');   // el dueño es quien inició sesión (RNE03)
        $publicacion->Tipo_Publicacion = $tipoPublicacion;
        $publicacion->save();

        return $publicacion;
    }

    // Datos propios de la adopción, para crear y para editar.
    // La edad se guarda en meses (RNE12): 2 años = 24, 4 meses = 4
    private function datosAdopcion(Request $request)
    {
        $edadEnMeses = $request->input('edad');

        if ($request->input('edad_unidad') == 'años')
        {
            $edadEnMeses = $edadEnMeses * 12;
        }

        return [
            'Edad_Meses' => $edadEnMeses,
            'Tamaño' => $request->input('tamanio'),
            'Estado_Salud' => $request->input('salud'),
            'Estado_Esterilizacion' => $request->input('esterilizacion'),
            'Ubicacion_Aproximada' => $request->input('ubicacion'),
        ];
    }

    private function guardarAdopcion(Request $request, $publicacion)
    {
        PublicacionAdopcion::create(['ID_Publicacion' => $publicacion->ID_Publicacion] + $this->datosAdopcion($request));
    }

    // Datos propios de la perdida o la encontrada, para crear y para editar
    private function datosAviso(Request $request)
    {
        return [
            'Nombre_Mascota' => $request->input('nombre_mascota') ?: null,
            'Caracteristicas' => $request->input('caracteristicas'),
            'Fecha_Suceso' => $request->input('fecha'),
            'Zona_Suceso' => $request->input('zona'),
            'Contacto' => $request->input('contacto'),
        ];
    }

    // Datos propios de la perdida o la encontrada. Vence a los 30 días (RNE17)
    private function guardarAviso(Request $request, $publicacion, $tipo)
    {
        // Estado_Perdida y Fecha_Vencimiento no están en $fillable: se asignan a mano
        $aviso = new PublicacionPerdida([
            'ID_Publicacion' => $publicacion->ID_Publicacion,
            'Tipo_Aviso' => $tipo,
        ] + $this->datosAviso($request));
        $aviso->Fecha_Vencimiento = now()->addDays(30)->toDateString();
        $aviso->save();
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
