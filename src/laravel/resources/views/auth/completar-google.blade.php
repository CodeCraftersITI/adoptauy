{{--
    Completar datos después de Continuar con Google (RF:0066).
    $google llega desde GoogleController con lo que mandó Google: correo, nombre y apellido.
    El correo no se puede cambiar: es el de la cuenta de Google y se guarda desde la sesión.
--}}
@extends('layouts.app')

@section('titulo', 'Completar datos')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <h1 class="pagina-titulo">Completar datos</h1>

    <p class="apagado">Ya casi está. Google nos pasó tu correo <strong>{{ $google['email'] }}</strong>.
        Para crear tu cuenta necesitamos tu cédula y tu teléfono. Revisá tu nombre y apellido.</p>

    <form action="/auth/google/completar" method="POST" class="tarjeta formulario formulario-grilla">
        @csrf

        @include('partials.campos-ci-telefono')

        {{-- old('nombre', valor) muestra lo que vino de Google la primera vez --}}
        <label class="campo">
            Nombre
            <input class="entrada" type="text" name="nombre" value="{{ old('nombre', $google['nombre']) }}" required>
            @error('nombre') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Apellido
            <input class="entrada" type="text" name="apellido" value="{{ old('apellido', $google['apellido']) }}" required>
            @error('apellido') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario ocupa-todo">Crear mi cuenta</button>
    </form>

    <p class="apagado texto-centro">Tu cuenta se crea sin contraseña: vas a ingresar con Google.</p>
</div>
@endsection
