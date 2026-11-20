{{--
    Publicar mascota: un único formulario para los tres tipos (corrección del docente).
    Adopción (RF:0017, RF:0024), Perdida (RF:0046) y Encontrada (RF:0064).
    $tipo (el que aparece marcado al abrir) llega desde PublicacionController.
    Editar (RF:0019, RF:0026, RF:0003) usa este mismo formulario: entonces también llega
    $publicacion, sus datos vienen cargados y el tipo no se puede cambiar.

    Cada bloque de campos dice en data-tipos para qué tipos es. El script de abajo muestra
    solo los bloques del tipo elegido y desactiva (disabled) los campos de los otros:
    un campo desactivado no se envía y el navegador no lo exige aunque tenga required.
--}}
@extends('layouts.app')

@php
    // Al editar se cargan los datos guardados; al publicar, $publicacion no existe y quedan vacíos.
    // old('campo', valor) muestra primero lo que el usuario escribió si hubo un error.
    $editando = isset($publicacion);
    $datosAdopcion = $editando && $publicacion->Tipo_Publicacion == 'Adopción' ? $publicacion->adopcion : null;
    $datosAviso = $editando && $publicacion->Tipo_Publicacion == 'Perdida' ? $publicacion->perdida : null;

    // La edad está guardada en meses: si son años justos se muestra en años (24 meses = 2 años)
    $edadUnidad = 'meses';
    $edad = $datosAdopcion ? $datosAdopcion->Edad_Meses : '';
    if ($datosAdopcion && $edad >= 12 && $edad % 12 == 0)
    {
        $edadUnidad = 'años';
        $edad = $edad / 12;
    }
@endphp

@section('titulo', $editando ? 'Editar publicación' : 'Publicar mascota')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    @if ($editando)
        <a class="detalle-volver" href="/publicaciones/{{ $publicacion->ID_Publicacion }}">← Volver a la publicación</a>
    @endif

    <h1 class="pagina-titulo">{{ $editando ? 'Editar publicación' : 'Publicar mascota' }}</h1>

    {{-- enctype="multipart/form-data" es obligatorio para enviar archivos --}}
    <form action="{{ $editando ? '/publicaciones/' . $publicacion->ID_Publicacion : '/publicar' }}" method="POST" enctype="multipart/form-data" class="tarjeta formulario formulario-grilla" id="form-publicar">
        @csrf
        @include('partials.antibot')
        @if ($editando)
            @method('PUT')
        @endif

        {{-- ¿Qué publica? Si hubo un error, old('tipo') vuelve a marcar el que había elegido --}}
        <fieldset class="ocupa-todo elegir-tipo">
            <legend>{{ $editando ? 'Tipo de publicación' : '¿Qué querés publicar?' }}</legend>
            <div class="pestanas">
                @foreach (['Adopción' => 'En adopción', 'Perdida' => 'Mascota perdida', 'Encontrada' => 'Mascota encontrada'] as $valor => $texto)
                    @continue ($editando && $valor != $tipo)
                    <label class="pestana">
                        <input type="radio" name="tipo" value="{{ $valor }}" {{ old('tipo', $tipo) == $valor ? 'checked' : '' }}>
                        {{ $texto }}
                    </label>
                @endforeach
            </div>
            @error('tipo') <span class="campo-error">{{ $message }}</span> @enderror
        </fieldset>

        <p class="apagado ocupa-todo" data-tipos="Perdida Encontrada">
            La publicación queda visible 30 días y la podés renovar. El contacto que cargues lo ve cualquier visitante.
        </p>

        {{-- Nombre: obligatorio en la perdida, opcional en la encontrada --}}
        <label class="campo" data-tipos="Perdida Encontrada">
            <span>Nombre de la mascota <span data-tipos="Encontrada">(opcional)</span></span>
            <input class="entrada" type="text" name="nombre_mascota" id="nombre-mascota" value="{{ old('nombre_mascota', $datosAviso->Nombre_Mascota ?? '') }}" maxlength="50">
            @error('nombre_mascota') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- ---------- Campos de todos los tipos ---------- --}}
        <label class="campo">
            Especie
            <select class="entrada" name="especie" required>
                <option value="">Elegí una opción</option>
                <option value="Perro" {{ old('especie', $publicacion->Especie ?? '') == 'Perro' ? 'selected' : '' }}>Perro</option>
                <option value="Gato" {{ old('especie', $publicacion->Especie ?? '') == 'Gato' ? 'selected' : '' }}>Gato</option>
            </select>
            @error('especie') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Raza
            <input class="entrada" type="text" name="raza" value="{{ old('raza', $publicacion->Raza ?? '') }}" placeholder="Ej.: mestizo" maxlength="50" required>
            @error('raza') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Sexo
            <select class="entrada" name="sexo" required>
                <option value="">Elegí una opción</option>
                <option value="Hembra" {{ old('sexo', $publicacion->Sexo ?? '') == 'Hembra' ? 'selected' : '' }}>Hembra</option>
                <option value="Macho" {{ old('sexo', $publicacion->Sexo ?? '') == 'Macho' ? 'selected' : '' }}>Macho</option>
            </select>
            @error('sexo') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- ---------- Solo adopción ---------- --}}
        <label class="campo" data-tipos="Adopción">
            Tamaño
            <select class="entrada" name="tamanio" required>
                <option value="">Elegí una opción</option>
                <option value="Pequeño" {{ old('tamanio', $datosAdopcion->Tamaño ?? '') == 'Pequeño' ? 'selected' : '' }}>Pequeño</option>
                <option value="Mediano" {{ old('tamanio', $datosAdopcion->Tamaño ?? '') == 'Mediano' ? 'selected' : '' }}>Mediano</option>
                <option value="Grande" {{ old('tamanio', $datosAdopcion->Tamaño ?? '') == 'Grande' ? 'selected' : '' }}>Grande</option>
            </select>
            @error('tamanio') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- Edad estimada: un número y la unidad. Se guarda en meses (2 años = 24) --}}
        <div class="campo" data-tipos="Adopción">
            <span>Edad estimada</span>
            <div class="edad">
                <input class="entrada solo-numeros" type="text" name="edad" value="{{ old('edad', $edad) }}" inputmode="numeric" maxlength="4" placeholder="Ej.: 4" aria-label="Edad estimada" required>
                <select class="entrada" name="edad_unidad" aria-label="Unidad de la edad" required>
                    <option value="meses" {{ old('edad_unidad', $edadUnidad) == 'meses' ? 'selected' : '' }}>meses</option>
                    <option value="años" {{ old('edad_unidad', $edadUnidad) == 'años' ? 'selected' : '' }}>años</option>
                </select>
            </div>
            @error('edad') <span class="campo-error">{{ $message }}</span> @enderror
            @error('edad_unidad') <span class="campo-error">{{ $message }}</span> @enderror
        </div>

        {{-- En pantalla dice "castrado" (pedido del docente); en la base sigue Estado_Esterilizacion --}}
        <label class="campo" data-tipos="Adopción">
            Castración
            <select class="entrada" name="esterilizacion" required>
                <option value="">Elegí una opción</option>
                <option value="Esterilizado" {{ old('esterilizacion', $datosAdopcion->Estado_Esterilizacion ?? '') == 'Esterilizado' ? 'selected' : '' }}>Castrado</option>
                <option value="No esterilizado" {{ old('esterilizacion', $datosAdopcion->Estado_Esterilizacion ?? '') == 'No esterilizado' ? 'selected' : '' }}>No castrado</option>
            </select>
            @error('esterilizacion') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo" data-tipos="Adopción">
            Ubicación aproximada
            <input class="entrada" type="text" name="ubicacion" value="{{ old('ubicacion', $datosAdopcion->Ubicacion_Aproximada ?? '') }}" placeholder="Barrio, sin dirección exacta" maxlength="100" required>
            @error('ubicacion') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo ocupa-todo" data-tipos="Adopción">
            Estado de salud
            <textarea class="entrada" name="salud" rows="3" maxlength="255" placeholder="Vacunas, desparasitación, cuidados especiales" required>{{ old('salud', $datosAdopcion->Estado_Salud ?? '') }}</textarea>
            @error('salud') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- ---------- Solo perdida y encontrada ---------- --}}
        <label class="campo" data-tipos="Perdida Encontrada">
            <span>
                <span data-tipos="Perdida">Fecha del extravío</span>
                <span data-tipos="Encontrada">Fecha en que la encontraste</span>
            </span>
            {{-- max = hoy: el navegador no deja elegir una fecha futura (el servidor también lo controla) --}}
            <input class="entrada" type="date" name="fecha" value="{{ old('fecha', $datosAviso->Fecha_Suceso ?? '') }}" max="{{ date('Y-m-d') }}" required>
            @error('fecha') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo" data-tipos="Perdida Encontrada">
            <span>
                <span data-tipos="Perdida">Zona del extravío</span>
                <span data-tipos="Encontrada">Zona donde la encontraste</span>
            </span>
            <input class="entrada" type="text" name="zona" value="{{ old('zona', $datosAviso->Zona_Suceso ?? '') }}" placeholder="Barrio o calles" maxlength="100" required>
            @error('zona') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo" data-tipos="Perdida Encontrada">
            Contacto (público)
            <input class="entrada solo-numeros" type="text" name="contacto" value="{{ old('contacto', $datosAviso->Contacto ?? '') }}" placeholder="Teléfono: 8 o 9 números" inputmode="numeric" maxlength="9" required>
            @error('contacto') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo ocupa-todo" data-tipos="Perdida Encontrada">
            Características
            <textarea class="entrada" name="caracteristicas" rows="3" maxlength="1000" placeholder="Color, collar, señas particulares" required>{{ old('caracteristicas', $datosAviso->Caracteristicas ?? '') }}</textarea>
            @error('caracteristicas') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- ---------- Fotos: de todos los tipos, cambia solo cuántas ---------- --}}
        {{-- name="fotos[]" con multiple: llegan todas las fotos juntas como un array.
             El Administrador no reemplaza fotos: adoptauy_admin no tiene DELETE (16.3.4.5) --}}
        @php $puedeCambiarFotos = !$editando || session('tipo') != 'Administrador'; @endphp
        <label class="campo ocupa-todo">
            Fotos
            @if ($editando)
                <span class="miniaturas-actuales">
                    @foreach ($publicacion->fotos as $foto)
                        <img class="miniatura" src="/storage/{{ $foto->URL_Foto }}" alt="Foto actual">
                    @endforeach
                </span>
                @if ($puedeCambiarFotos)
                    <span class="campo-ayuda">Si no elegís fotos nuevas, quedan estas. Si elegís, reemplazan a todas.</span>
                @else
                    <span class="campo-ayuda">Las fotos las cambia solo quien publicó.</span>
                @endif
            @endif
            @if ($puedeCambiarFotos)
                <input class="entrada" type="file" name="fotos[]" accept=".jpg,.jpeg,.png,.webp" multiple {{ $editando ? '' : 'required' }}>
                <span class="campo-ayuda">
                    Entre 1 y <span data-tipos="Adopción">6</span><span data-tipos="Perdida Encontrada">3</span>
                    fotos JPG, PNG o WEBP, de hasta 8 MB cada una.
                </span>
            @endif
            @error('fotos') <span class="campo-error">{{ $message }}</span> @enderror
            @error('fotos.*') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario ocupa-todo">{{ $editando ? 'Guardar cambios' : 'Publicar' }}</button>
    </form>
</div>

<script>
    const formulario = document.getElementById('form-publicar');

    // Muestra los bloques del tipo elegido y oculta y desactiva los de los otros tipos
    function mostrarCamposDelTipo()
    {
        const tipo = formulario.querySelector('input[name="tipo"]:checked').value;

        formulario.querySelectorAll('[data-tipos]').forEach(function (bloque)
        {
            const esDelTipo = bloque.dataset.tipos.split(' ').includes(tipo);
            bloque.hidden = !esDelTipo;

            bloque.querySelectorAll('input, select, textarea').forEach(function (campo)
            {
                campo.disabled = !esDelTipo;
            });
        });

        // El nombre es obligatorio solo en la mascota perdida (en la encontrada es opcional)
        document.getElementById('nombre-mascota').required = (tipo == 'Perdida');
    }

    formulario.querySelectorAll('input[name="tipo"]').forEach(function (opcion)
    {
        opcion.addEventListener('change', mostrarCamposDelTipo);
    });

    mostrarCamposDelTipo();
</script>
@endsection
