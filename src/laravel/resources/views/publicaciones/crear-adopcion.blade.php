{{-- Publicar mascota en adopción (RF:0017, RF:0024) --}}
@extends('layouts.app')

@section('titulo', 'Publicar en adopción')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <h1 class="pagina-titulo">Publicar mascota</h1>

    @include('partials.pestanas-publicar')

    {{-- enctype="multipart/form-data" es obligatorio para enviar archivos --}}
    <form action="/publicar/adopcion" method="POST" enctype="multipart/form-data" class="tarjeta formulario formulario-grilla">
        @csrf

        @include('partials.campos-animal')

        <label class="campo">
            Tamaño
            <select class="entrada" name="tamanio" required>
                <option value="">Elegí una opción</option>
                <option value="Pequeño" {{ old('tamanio') == 'Pequeño' ? 'selected' : '' }}>Pequeño</option>
                <option value="Mediano" {{ old('tamanio') == 'Mediano' ? 'selected' : '' }}>Mediano</option>
                <option value="Grande" {{ old('tamanio') == 'Grande' ? 'selected' : '' }}>Grande</option>
            </select>
            @error('tamanio') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- La edad es estimada: se cargan años y meses y se guarda en meses --}}
        <label class="campo">
            Edad: años
            <input class="entrada" type="number" name="edad_anios" min="0" max="30" value="{{ old('edad_anios', 0) }}" required>
            @error('edad_anios') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Edad: meses
            <input class="entrada" type="number" name="edad_meses" min="0" max="11" value="{{ old('edad_meses', 0) }}" required>
            @error('edad_meses') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Esterilización
            <select class="entrada" name="esterilizacion" required>
                <option value="">Elegí una opción</option>
                <option value="Esterilizado" {{ old('esterilizacion') == 'Esterilizado' ? 'selected' : '' }}>Esterilizado</option>
                <option value="No esterilizado" {{ old('esterilizacion') == 'No esterilizado' ? 'selected' : '' }}>No esterilizado</option>
            </select>
            @error('esterilizacion') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Ubicación aproximada
            <input class="entrada" type="text" name="ubicacion" value="{{ old('ubicacion') }}" placeholder="Barrio, sin dirección exacta" required>
            @error('ubicacion') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo ocupa-todo">
            Estado de salud
            <textarea class="entrada" name="salud" rows="3" maxlength="255" placeholder="Vacunas, desparasitación, cuidados especiales" required>{{ old('salud') }}</textarea>
            @error('salud') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- name="fotos[]" con multiple: llegan todas las fotos juntas como un array --}}
        <label class="campo ocupa-todo">
            Fotos
            <input class="entrada" type="file" name="fotos[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
            <span class="campo-ayuda">Entre 1 y 6 fotos JPG, PNG o WEBP, de hasta 8 MB cada una.</span>
            @error('fotos') <span class="campo-error">{{ $message }}</span> @enderror
            @error('fotos.*') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario ocupa-todo">Publicar en adopción</button>
    </form>
</div>
@endsection
