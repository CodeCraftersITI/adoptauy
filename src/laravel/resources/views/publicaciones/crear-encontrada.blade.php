{{-- Publicar mascota encontrada (RF:0064): una mascota ajena que alguien encontró --}}
@extends('layouts.app')

@section('titulo', 'Publicar mascota encontrada')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <h1 class="pagina-titulo">Publicar mascota</h1>

    @include('partials.pestanas-publicar')

    <p class="apagado">¿Encontraste una mascota que no es tuya? Publicala para que su familia la encuentre. La publicación queda visible 30 días y la podés renovar. El contacto que cargues lo ve cualquier visitante.</p>

    <form action="/publicar/encontrada" method="POST" enctype="multipart/form-data" class="tarjeta formulario formulario-grilla">
        @csrf

        <label class="campo">
            Nombre de la mascota (opcional)
            <input class="entrada" type="text" name="nombre_mascota" value="{{ old('nombre_mascota') }}" placeholder="Si lo sabés, por ejemplo por la chapita">
            @error('nombre_mascota') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        @include('partials.campos-animal')

        <label class="campo">
            Fecha en que la encontraste
            {{-- max = hoy: el navegador no deja elegir una fecha futura (el servidor también lo controla) --}}
            <input class="entrada" type="date" name="fecha_hallazgo" value="{{ old('fecha_hallazgo') }}" max="{{ date('Y-m-d') }}" required>
            @error('fecha_hallazgo') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Zona donde la encontraste
            <input class="entrada" type="text" name="zona" value="{{ old('zona') }}" placeholder="Barrio o calles" required>
            @error('zona') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Contacto (público)
            <input class="entrada" type="text" name="contacto" value="{{ old('contacto') }}" placeholder="Teléfono: 8 o 9 números" inputmode="numeric" required>
            @error('contacto') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo ocupa-todo">
            Características
            <textarea class="entrada" name="caracteristicas" rows="3" maxlength="1000" placeholder="Color, collar, señas particulares" required>{{ old('caracteristicas') }}</textarea>
            @error('caracteristicas') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo ocupa-todo">
            Fotos
            <input class="entrada" type="file" name="fotos[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
            <span class="campo-ayuda">Entre 1 y 3 fotos JPG, PNG o WEBP, de hasta 8 MB cada una.</span>
            @error('fotos') <span class="campo-error">{{ $message }}</span> @enderror
            @error('fotos.*') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario ocupa-todo">Publicar mascota encontrada</button>
    </form>
</div>
@endsection
