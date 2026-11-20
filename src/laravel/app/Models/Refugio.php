<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Refugio u organización (tabla Refugio, 15.2.1). RF:0034.
 * Estado_Validacion: 'Pendiente de validación', 'Validado', 'Rechazado',
 * 'Suspendido' o 'Dado de baja' (13.2.1, RNE24).
 */
class Refugio extends Model
{
    use HasFactory;
    use ColumnasPermitidas;

    // Columnas que puede leer cada usuario de MySQL (16.3.4): sin los documentos ni el motivo
    // de rechazo. El propio Refugio, el sistema y el Administrador leen todas
    public const COLUMNAS_POR_CONEXION = [
        'login' => ['ID_Usuario', 'Estado_Validacion'],
        'publico' => ['ID_Usuario', 'Nombre_Refugio', 'Ubicacion', 'Latitud', 'Longitud', 'Estado_Validacion', 'URL_Foto'],
        'particular' => ['ID_Usuario', 'Nombre_Refugio', 'Ubicacion', 'Latitud', 'Longitud', 'Estado_Validacion', 'URL_Foto'],
    ];

    protected $table = 'Refugio';
    protected $primaryKey = 'ID_Usuario';
    public $incrementing = false;
    public $timestamps = false;

    // Estado_Validacion no está: el estado lo cambia solo el Administrador
    protected $fillable = [
        'ID_Usuario', 'Nombre_Refugio', 'Certificado_Personeria', 'Comprobante_Domicilio',
        'Ubicacion', 'Latitud', 'Longitud', 'URL_Foto',
    ];

    // Reglas y mensajes del nombre y la ubicación del refugio. Las usan la solicitud de
    // registro (RF:0034) y el perfil (RF:0029)
    public const REGLAS = [
        'nombre_refugio' => 'required|min:2|max:100',
        'ubicacion' => 'required|max:255',
    ];

    public const MENSAJES = [
        'nombre_refugio.required' => 'Ingresá el nombre del refugio.',
        'nombre_refugio.min' => 'El nombre debe tener al menos 2 letras.',
        'nombre_refugio.max' => 'El nombre puede tener hasta 100 caracteres.',
        'ubicacion.required' => 'Ingresá la ubicación del refugio.',
        'ubicacion.max' => 'La ubicación puede tener hasta 255 caracteres.',
    ];

    // Los contactos se cargan en un cuadro de texto, uno por línea: se separan, se limpian
    // y se sacan las líneas vacías y repetidas
    public static function contactosDesdeTexto($texto)
    {
        return array_values(array_unique(array_filter(array_map('trim', explode("\n", $texto ?? '')))));
    }

    // Reglas de la lista de contactos (al menos uno, de hasta 255 caracteres cada uno)
    public const REGLAS_CONTACTOS = [
        'lista_contactos' => 'required|array|min:1',
        'lista_contactos.*' => 'max:255',
    ];

    public const MENSAJES_CONTACTOS = [
        'lista_contactos.required' => 'Ingresá al menos un dato de contacto.',
        'lista_contactos.min' => 'Ingresá al menos un dato de contacto.',
        'lista_contactos.*.max' => 'Cada dato de contacto puede tener hasta 255 caracteres.',
    ];

    // Guarda nombre, ubicación y contactos. La usan "Mi perfil" (RF:0029) y el Administrador
    // (RF:0007). Los contactos se reemplazan por la lista nueva (atributo multivaluado)
    public function actualizarDatos($nombre, $ubicacion, $contactos)
    {
        DB::transaction(function () use ($nombre, $ubicacion, $contactos)
        {
            $this->Nombre_Refugio = $nombre;
            $this->Ubicacion = $ubicacion;
            $this->save();

            RefugioDatoContacto::where('ID_Usuario', $this->ID_Usuario)->delete();
            foreach ($contactos as $contacto)
            {
                RefugioDatoContacto::create(['ID_Usuario' => $this->ID_Usuario, 'Dato_Contacto' => $contacto]);
            }
        });
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'ID_Usuario');
    }

    // Un refugio tiene uno o más datos de contacto
    public function contactos()
    {
        return $this->hasMany(RefugioDatoContacto::class, 'ID_Usuario');
    }

    // Cuentas para donaciones, en el orden en que se cargaron (RF:0035 a RF:0037)
    public function cuentas()
    {
        return $this->hasMany(CuentaBancaria::class, 'ID_Usuario')->orderBy('ID_Cuenta_Banco');
    }

    // ¿Ya marcó su ubicación en el mapa? Sin latitud y longitud no aparece en el mapa
    public function tieneUbicacion()
    {
        return $this->Latitud !== null && $this->Longitud !== null;
    }

    // Baja lógica (RF:0006, 13.2.1): no puede iniciar sesión, sale del mapa y del listado, y sus
    // publicaciones activas pasan a Archivado o Archivada. Es definitiva. La usan el panel de
    // refugios (AdminRefugioController) y la resolución de reportes (AdminReporteController).
    public function darDeBaja()
    {
        // El refugio, su cuenta y sus publicaciones cambian juntos: o todo o nada
        DB::transaction(function ()
        {
            $this->Estado_Validacion = 'Dado de baja';
            $this->save();

            $this->usuario->Estado_Usuario = 'Baja';
            $this->usuario->save();

            // Las Adoptadas y Resueltas quedan como están
            PublicacionAdopcion::where('Estado_Adopcion', 'En adopción')
                ->whereHas('publicacion', function ($publicacion)
                {
                    $publicacion->where('ID_Usuario', $this->ID_Usuario);
                })
                ->update(['Estado_Adopcion' => 'Archivado']);

            PublicacionPerdida::where('Estado_Perdida', 'Vigente')
                ->whereHas('publicacion', function ($publicacion)
                {
                    $publicacion->where('ID_Usuario', $this->ID_Usuario);
                })
                ->update(['Estado_Perdida' => 'Archivada']);
        });
    }
}
