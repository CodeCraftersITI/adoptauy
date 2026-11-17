{{--
    Reportar una publicación, a un particular o a un refugio (RF:0059).
    $objeto (qué se reporta) y $motivos (la lista de 13.2.5 de ese tipo) llegan desde ReporteController.
--}}
@extends('layouts.app')

@section('titulo', 'Reportar')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <a class="detalle-volver" href="{{ $objeto['volver'] }}">← Volver</a>

    <h1 class="pagina-titulo">Reportar</h1>
    <p class="apagado">{{ $objeto['tipo'] }}: <strong>{{ $objeto['descripcion'] }}</strong></p>

    <form action="/reportar" method="POST" class="tarjeta formulario">
        @csrf
        @include('partials.antibot')

        {{-- Qué se reporta y a dónde volver viajan ocultos; el servidor los vuelve a comprobar --}}
        @if ($objeto['publicacion'])
            <input type="hidden" name="publicacion" value="{{ $objeto['publicacion']->ID_Publicacion }}">
        @else
            <input type="hidden" name="usuario" value="{{ $objeto['usuario']->ID_Usuario }}">
            <input type="hidden" name="volver" value="{{ $objeto['volver'] }}">
        @endif

        <fieldset class="elegir-motivo">
            <legend>¿Por qué lo reportás?</legend>
            @foreach ($motivos as $motivo)
                <label class="consentimiento">
                    <input type="radio" name="motivo" value="{{ $motivo }}" {{ old('motivo') == $motivo ? 'checked' : '' }} required>
                    {{ $motivo }}
                </label>
            @endforeach
            @error('motivo') <span class="campo-error">{{ $message }}</span> @enderror
        </fieldset>

        {{-- Solo para "Otro" (13.2.5) --}}
        <label class="campo">
            Detalle <span class="campo-ayuda">(obligatorio si elegiste «Otro»)</span>
            <textarea class="entrada" name="detalle" rows="3" maxlength="500">{{ old('detalle') }}</textarea>
            @error('detalle') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <p class="apagado">El Administrador revisa cada reporte. Quien publicó no ve quién lo reportó.</p>

        <button type="submit" class="btn btn-peligro">Enviar reporte</button>
    </form>
</div>
@endsection
