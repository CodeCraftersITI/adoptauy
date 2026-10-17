{{-- Registro de Particular (RF:0039). El registro de refugio (RF:0034) se suma después. --}}
@extends('layouts.app')

@section('titulo', 'Crear cuenta')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <h1 class="pagina-titulo">Crear cuenta</h1>

    {{-- old('campo') vuelve a mostrar lo que el usuario escribió si hubo un error --}}
    <form action="/registro" method="POST" class="tarjeta formulario formulario-grilla">
        @csrf

        <label class="campo">
            Cédula de identidad
            <input class="entrada" type="text" name="ci" value="{{ old('ci') }}" placeholder="Sin puntos ni guion" required>
            @error('ci') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Teléfono
            <input class="entrada" type="tel" name="telefono" value="{{ old('telefono') }}" placeholder="09X XXX XXX" required>
            @error('telefono') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Nombre
            <input class="entrada" type="text" name="nombre" value="{{ old('nombre') }}" required>
            @error('nombre') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Apellido
            <input class="entrada" type="text" name="apellido" value="{{ old('apellido') }}" required>
            @error('apellido') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Correo electrónico
            <input class="entrada" type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- La contraseña nunca se vuelve a mostrar, por eso no tiene old() --}}
        <label class="campo">
            Contraseña
            <input class="entrada" type="password" name="password" placeholder="Mínimo 8 caracteres" required>
            @error('password') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario ocupa-todo">Crear cuenta</button>
    </form>

    <p class="texto-centro">¿Ya tenés cuenta? <a href="/ingresar">Ingresar</a></p>
</div>
@endsection
