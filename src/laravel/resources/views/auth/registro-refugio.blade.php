{{-- Solicitud de registro de refugio (RF:0034) --}}
@extends('layouts.app')

@section('titulo', 'Registrar refugio')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <h1 class="pagina-titulo">Crear cuenta</h1>

    @include('partials.pestanas-registro')

    <p class="apagado">El Administrador revisa la documentación en un máximo de 5 días hábiles.
        Hasta que el refugio esté validado no se puede iniciar sesión.</p>

    {{-- enctype="multipart/form-data" es obligatorio para que el formulario pueda enviar archivos --}}
    <form action="/registro-refugio" method="POST" enctype="multipart/form-data" class="tarjeta formulario formulario-grilla">
        @csrf

        <label class="campo">
            Nombre del refugio
            <input class="entrada" type="text" name="nombre_refugio" value="{{ old('nombre_refugio') }}" required>
            @error('nombre_refugio') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Teléfono o red social
            <input class="entrada" type="text" name="contacto" value="{{ old('contacto') }}" required>
            @error('contacto') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo ocupa-todo">
            Ubicación
            <input class="entrada" type="text" name="ubicacion" value="{{ old('ubicacion') }}" placeholder="Dirección o barrio" required>
            @error('ubicacion') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        {{-- Los archivos no se pueden volver a cargar con old(): si hay un error hay que elegirlos de nuevo --}}
        <label class="campo">
            Certificado de personería
            <input class="entrada" type="file" name="certificado" accept=".pdf,.jpg,.jpeg,.png" required>
            <span class="campo-ayuda">PDF, JPG o PNG, hasta 8 MB.</span>
            @error('certificado') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Comprobante de domicilio
            <input class="entrada" type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png" required>
            <span class="campo-ayuda">PDF, JPG o PNG, hasta 8 MB.</span>
            @error('comprobante') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Correo electrónico
            <input class="entrada" type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Contraseña
            <input class="entrada" type="password" name="password" placeholder="Mínimo 8 caracteres" required>
            @error('password') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-primario ocupa-todo">Enviar solicitud</button>
    </form>

    <p class="texto-centro">¿Ya tenés cuenta? <a href="/ingresar">Ingresar</a></p>
</div>
@endsection
