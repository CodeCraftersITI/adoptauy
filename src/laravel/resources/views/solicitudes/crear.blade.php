{{--
    Solicitar adopción (RF:0049), pantalla del prototipo.
    $publicacion (la mascota) y $particular (quien solicita) llegan desde SolicitudController.
--}}
@extends('layouts.app')

@section('titulo', 'Solicitar adopción')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <a class="detalle-volver" href="/publicaciones/{{ $publicacion->ID_Publicacion }}">← Volver a la publicación</a>

    <h1 class="pagina-titulo">Solicitar adopción</h1>

    {{-- La mascota que se pide --}}
    <div class="solicitud-mascota">
        <img class="miniatura" src="{{ $publicacion->primeraFoto() }}" alt="">
        <div>
            <strong>{{ $publicacion->Especie }} · {{ $publicacion->Raza }}</strong>
            <div class="apagado">{{ $publicacion->adopcion->edadTexto() }} · {{ $publicacion->adopcion->Ubicacion_Aproximada }}</div>
        </div>
    </div>

    <form action="/publicaciones/{{ $publicacion->ID_Publicacion }}/solicitar" method="POST" class="tarjeta formulario">
        @csrf

        <strong>Estos datos se van a compartir con quien publicó</strong>
        <dl class="datos">
            <div><dt>Nombre</dt><dd>{{ $particular->Nombre }} {{ $particular->Apellido }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ $particular->Telefono }}</dd></div>
            <div><dt>Correo</dt><dd>{{ $particular->usuario->Email }}</dd></div>
        </dl>
        <span class="apagado">Tu cédula nunca se comparte.</span>

        {{-- Consentimiento obligatorio (Ley 18.331): el controlador valida 'accepted' --}}
        <label class="consentimiento">
            <input type="checkbox" name="acepto" value="1" {{ old('acepto') ? 'checked' : '' }}>
            Acepto que mi nombre, teléfono y correo se compartan con el publicante para coordinar la adopción (Ley 18.331).
        </label>
        @error('acepto') <span class="campo-error">{{ $message }}</span> @enderror

        <button type="submit" class="btn btn-primario">Enviar solicitud</button>
    </form>
</div>
@endsection
