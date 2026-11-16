{{--
    Publicar mascota: un único formulario para los tres tipos (corrección del docente).
    Adopción (RF:0017, RF:0024), Perdida (RF:0046) y Encontrada (RF:0064).
    $tipo (el que aparece marcado al abrir) llega desde PublicacionController.

    Cada bloque de campos dice en data-tipos para qué tipos es. El script de abajo muestra
    solo los bloques del tipo elegido y desactiva (disabled) los campos de los otros:
    un campo desactivado no se envía y el navegador no lo exige aunque tenga required.
--}}
@extends('layouts.app')

@section('titulo', 'Publicar mascota')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <h1 class="pagina-titulo">Publicar mascota</h1>

    {{-- enctype="multipart/form-data" es obligatorio para enviar archivos --}}
    <form action="/publicar" method="POST" enctype="multipart/form-data" class="tarjeta formulario formulario-grilla" id="form-publicar">
        @csrf

        {{-- ¿Qué publica? Si hubo un error, old('tipo') vuelve a marcar el que había elegido --}}
        <fieldset class="ocupa-todo elegir-tipo">
            <legend>¿Qué querés publicar?</legend>
            <div class="pestanas">
                @foreach (['Adopción' => 'En adopción', 'Perdida' => 'Mascota perdida', 'Encontrada' => 'Mascota encontrada'] as $valor => $texto)
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
            <input class="entrada" type="text" name="nombre_mascota" id="nombre-mascota" value="{{ old('nombre_mascota') }}" maxlength="50">
            @error('nombre_mascota') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- ---------- Campos de todos los tipos ---------- --}}
        <label class="campo">
            Especie
            <select class="entrada" name="especie" required>
                <option value="">Elegí una opción</option>
                <option value="Perro" {{ old('especie') == 'Perro' ? 'selected' : '' }}>Perro</option>
                <option value="Gato" {{ old('especie') == 'Gato' ? 'selected' : '' }}>Gato</option>
            </select>
            @error('especie') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Raza
            <input class="entrada" type="text" name="raza" value="{{ old('raza') }}" placeholder="Ej.: mestizo" maxlength="50" required>
            @error('raza') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Sexo
            <select class="entrada" name="sexo" required>
                <option value="">Elegí una opción</option>
                <option value="Hembra" {{ old('sexo') == 'Hembra' ? 'selected' : '' }}>Hembra</option>
                <option value="Macho" {{ old('sexo') == 'Macho' ? 'selected' : '' }}>Macho</option>
            </select>
            @error('sexo') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- ---------- Solo adopción ---------- --}}
        <label class="campo" data-tipos="Adopción">
            Tamaño
            <select class="entrada" name="tamanio" required>
                <option value="">Elegí una opción</option>
                <option value="Pequeño" {{ old('tamanio') == 'Pequeño' ? 'selected' : '' }}>Pequeño</option>
                <option value="Mediano" {{ old('tamanio') == 'Mediano' ? 'selected' : '' }}>Mediano</option>
                <option value="Grande" {{ old('tamanio') == 'Grande' ? 'selected' : '' }}>Grande</option>
            </select>
            @error('tamanio') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- Edad estimada: un número y la unidad. Se guarda en meses (2 años = 24) --}}
        <div class="campo" data-tipos="Adopción">
            <span>Edad estimada</span>
            <div class="edad">
                <input class="entrada solo-numeros" type="text" name="edad" value="{{ old('edad') }}" inputmode="numeric" maxlength="4" placeholder="Ej.: 4" aria-label="Edad estimada" required>
                <select class="entrada" name="edad_unidad" aria-label="Unidad de la edad" required>
                    <option value="meses" {{ old('edad_unidad') == 'meses' ? 'selected' : '' }}>meses</option>
                    <option value="años" {{ old('edad_unidad') == 'años' ? 'selected' : '' }}>años</option>
                </select>
            </div>
            @error('edad') <span class="campo-error">{{ $message }}</span> @enderror
            @error('edad_unidad') <span class="campo-error">{{ $message }}</span> @enderror
        </div>

        <label class="campo" data-tipos="Adopción">
            Esterilización
            <select class="entrada" name="esterilizacion" required>
                <option value="">Elegí una opción</option>
                <option value="Esterilizado" {{ old('esterilizacion') == 'Esterilizado' ? 'selected' : '' }}>Esterilizado</option>
                <option value="No esterilizado" {{ old('esterilizacion') == 'No esterilizado' ? 'selected' : '' }}>No esterilizado</option>
            </select>
            @error('esterilizacion') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo" data-tipos="Adopción">
            Ubicación aproximada
            <input class="entrada" type="text" name="ubicacion" value="{{ old('ubicacion') }}" placeholder="Barrio, sin dirección exacta" maxlength="100" required>
            @error('ubicacion') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo ocupa-todo" data-tipos="Adopción">
            Estado de salud
            <textarea class="entrada" name="salud" rows="3" maxlength="255" placeholder="Vacunas, desparasitación, cuidados especiales" required>{{ old('salud') }}</textarea>
            @error('salud') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- ---------- Solo perdida y encontrada ---------- --}}
        <label class="campo" data-tipos="Perdida Encontrada">
            <span>
                <span data-tipos="Perdida">Fecha del extravío</span>
                <span data-tipos="Encontrada">Fecha en que la encontraste</span>
            </span>
            {{-- max = hoy: el navegador no deja elegir una fecha futura (el servidor también lo controla) --}}
            <input class="entrada" type="date" name="fecha" value="{{ old('fecha') }}" max="{{ date('Y-m-d') }}" required>
            @error('fecha') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo" data-tipos="Perdida Encontrada">
            <span>
                <span data-tipos="Perdida">Zona del extravío</span>
                <span data-tipos="Encontrada">Zona donde la encontraste</span>
            </span>
            <input class="entrada" type="text" name="zona" value="{{ old('zona') }}" placeholder="Barrio o calles" maxlength="100" required>
            @error('zona') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo" data-tipos="Perdida Encontrada">
            Contacto (público)
            <input class="entrada solo-numeros" type="text" name="contacto" value="{{ old('contacto') }}" placeholder="Teléfono: 8 o 9 números" inputmode="numeric" maxlength="9" required>
            @error('contacto') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo ocupa-todo" data-tipos="Perdida Encontrada">
            Características
            <textarea class="entrada" name="caracteristicas" rows="3" maxlength="1000" placeholder="Color, collar, señas particulares" required>{{ old('caracteristicas') }}</textarea>
            @error('caracteristicas') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- ---------- Fotos: de todos los tipos, cambia solo cuántas ---------- --}}
        {{-- name="fotos[]" con multiple: llegan todas las fotos juntas como un array --}}
        <label class="campo ocupa-todo">
            Fotos
            <input class="entrada" type="file" name="fotos[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
            <span class="campo-ayuda">
                Entre 1 y <span data-tipos="Adopción">6</span><span data-tipos="Perdida Encontrada">3</span>
                fotos JPG, PNG o WEBP, de hasta 8 MB cada una.
            </span>
            @error('fotos') <span class="campo-error">{{ $message }}</span> @enderror
            @error('fotos.*') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario ocupa-todo">Publicar</button>
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
