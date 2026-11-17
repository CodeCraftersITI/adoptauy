{{--
    "Mi perfil" (RF:0016 Particular, RF:0029 Refugio) y contraseña (RF:0067).
    $usuario llega desde PerfilController. La cédula y el correo se muestran pero no se pueden cambiar.
--}}
@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('contenido')
@php
    $esRefugio = $usuario->Tipo_Usuario == 'Refugio';
    $tieneContrasena = $usuario->Contraseña !== null;
@endphp

<div class="formulario-angosto formulario-ancho">
    <a class="detalle-volver" href="/cuenta">← Volver a Mi cuenta</a>

    <h1 class="pagina-titulo">Mi perfil</h1>

    {{-- ---------- Datos ---------- --}}
    <form action="/perfil/datos" method="POST" enctype="multipart/form-data" class="tarjeta formulario formulario-grilla">
        @csrf
        @method('PUT')

        <h2 class="detalle-subtitulo ocupa-todo">Mis datos</h2>

        {{-- Datos que no se cambian desde acá --}}
        <dl class="datos ocupa-todo">
            <div><dt>Correo</dt><dd>{{ $usuario->Email }}</dd></div>
            @if (!$esRefugio)
                <div><dt>Cédula</dt><dd>{{ $usuario->particular->CI }}</dd></div>
            @endif
        </dl>

        @if ($esRefugio)
            @php $refugio = $usuario->refugio; @endphp

            @include('partials.campos-refugio', ['refugio' => $refugio])
            <span class="campo-ayuda ocupa-todo">El punto en el mapa se marca en <a href="/mi-refugio">Mi refugio</a>.</span>

            <label class="campo ocupa-todo">
                Foto del refugio
                @if ($refugio->URL_Foto)
                    <img class="miniatura" src="/storage/{{ $refugio->URL_Foto }}" alt="Foto actual del refugio">
                @endif
                <input class="entrada" type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
                <span class="campo-ayuda">JPG, PNG o WEBP, de hasta 8 MB. Si elegís una, reemplaza a la actual.</span>
                @error('foto') <span class="campo-error">{{ $message }}</span> @enderror
            </label>
        @else
            @php $particular = $usuario->particular; @endphp

            @include('partials.campos-particular', ['particular' => $particular])
        @endif

        <button type="submit" class="btn btn-primario ocupa-todo">Guardar mis datos</button>
    </form>

    {{-- ---------- Contraseña (RF:0067) ---------- --}}
    <form action="/perfil/contrasena" method="POST" class="tarjeta formulario">
        @csrf
        @method('PUT')

        <h2 class="detalle-subtitulo">{{ $tieneContrasena ? 'Cambiar contraseña' : 'Definir contraseña' }}</h2>

        @if (!$tieneContrasena)
            {{-- Cuenta creada con Google: todavía no tiene contraseña --}}
            <p class="apagado">Tu cuenta se creó con Google. Si definís una contraseña, también vas a poder ingresar con tu correo y contraseña.</p>
        @else
            <label class="campo">
                Contraseña actual
                <input class="entrada" type="password" name="password_actual" autocomplete="current-password" required>
                @error('password_actual') <span class="campo-error">{{ $message }}</span> @enderror
            </label>
        @endif

        <label class="campo">
            Contraseña nueva
            <input class="entrada" type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
            <span class="campo-ayuda">Entre 8 y 72 caracteres.</span>
            @error('password') <span class="campo-error">{{ $message }}</span> @enderror
        </label>

        <label class="campo">
            Repetí la contraseña nueva
            <input class="entrada" type="password" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" required>
        </label>

        <button type="submit" class="btn btn-primario">{{ $tieneContrasena ? 'Cambiar contraseña' : 'Definir contraseña' }}</button>
    </form>
</div>
@endsection
