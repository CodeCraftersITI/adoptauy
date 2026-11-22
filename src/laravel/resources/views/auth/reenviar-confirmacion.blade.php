{{-- Pedir otro correo de confirmación (RF:0043), por si el primero no llegó --}}
@extends('layouts.app')

@section('titulo', 'Confirmar correo')

@section('contenido')
<div class="formulario-angosto">
    <h1 class="pagina-titulo">Confirmar correo</h1>

    <p class="apagado">Escribí el correo con el que te registraste y te mandamos otro enlace para confirmarlo.</p>

    <form action="/confirmar-correo" method="POST" class="tarjeta formulario">
        @csrf
        @include('partials.antibot')

        <label class="campo">
            Correo electrónico
            <input class="entrada" type="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" required>
            @error('email') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario">Enviar enlace</button>
    </form>

    <p class="texto-centro"><a href="/ingresar">Volver a ingresar</a></p>
</div>
@endsection
