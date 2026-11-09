{{-- Inicio de sesión (RF:0022) --}}
@extends('layouts.app')

@section('titulo', 'Ingresar')

@section('contenido')
<div class="formulario-angosto">
    <h1 class="pagina-titulo">Ingresar</h1>

    <form action="/ingresar" method="POST" class="tarjeta formulario">
        @csrf

        <label class="campo">
            Correo electrónico
            <input class="entrada" type="email" name="email" value="{{ old('email') }}" placeholder="tu@correo.com" required>
            @error('email') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Contraseña
            <input class="entrada" type="password" name="password" required>
            @error('password') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario">Ingresar</button>
    </form>

    <p class="texto-centro">¿No tenés cuenta? <a href="/registro">Crear cuenta</a></p>
</div>
@endsection
