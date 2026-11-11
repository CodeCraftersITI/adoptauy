{{--
    Detalle de una publicación: mascota en adopción, perdida o encontrada.
    $publicacion y $esPublicante llegan desde DetallePublicacionController.
--}}
@extends('layouts.app')

@section('titulo', $publicacion->Tipo_Publicacion == 'Adopción' ? $publicacion->Especie . ' en adopción' : $publicacion->perdida->nombreVisible())

@section('contenido')
@php
    // Atajos para no repetir $publicacion->adopcion y $publicacion->perdida en toda la vista
    $esAdopcion = $publicacion->Tipo_Publicacion == 'Adopción';
    $adopcion = $publicacion->adopcion;
    $aviso = $publicacion->perdida;
@endphp

<div class="detalle-pagina">
    <a class="detalle-volver" href="/{{ $esAdopcion ? 'adopcion' : ($aviso->esEncontrada() ? 'encontradas' : 'perdidas') }}">← Volver al listado</a>

    {{-- El publicante ve su publicación aunque ya no sea pública (archivada, resuelta, adoptada) --}}
    @if (!$publicacion->esPublica())
        <div class="mensaje mensaje-error">Esta publicación no está visible para el público: solo la ves vos.</div>
    @endif

    <div class="detalle">
        {{-- Fotos: la primera grande y las demás chicas; cada una se abre en tamaño completo --}}
        <div class="detalle-fotos">
            <a href="/storage/{{ $publicacion->fotos->first()->URL_Foto }}" target="_blank">
                <img class="detalle-foto" src="/storage/{{ $publicacion->fotos->first()->URL_Foto }}" alt="Foto principal de la mascota">
            </a>

            @if ($publicacion->fotos->count() > 1)
                <div class="detalle-miniaturas">
                    @foreach ($publicacion->fotos as $foto)
                        <a href="/storage/{{ $foto->URL_Foto }}" target="_blank">
                            <img src="/storage/{{ $foto->URL_Foto }}" alt="Foto {{ $loop->iteration }} de {{ $publicacion->fotos->count() }}">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="detalle-datos">
            {{-- Encabezado: estado (o tipo de aviso), título y subtítulo --}}
            @if ($esAdopcion)
                <span class="estado">{{ $adopcion->Estado_Adopcion }}</span>
                <h1 class="pagina-titulo">{{ $publicacion->Especie }} · {{ $publicacion->Raza }}</h1>
                <p class="apagado">{{ $adopcion->edadTexto() }} · Publicado el {{ date('d/m/Y', strtotime($publicacion->Fecha_Creacion)) }}</p>
            @else
                <span class="estado">{{ $aviso->esEncontrada() ? 'Mascota encontrada' : 'Mascota perdida' }}
                    @if ($aviso->Estado_Perdida != 'Vigente') · {{ $aviso->Estado_Perdida }} @endif
                </span>
                <h1 class="pagina-titulo">{{ $aviso->nombreVisible() }}</h1>
                <p class="apagado">{{ $publicacion->Especie }} · {{ $publicacion->Raza }} · {{ $publicacion->Sexo }}</p>
            @endif

            {{-- Datos en una lista de definiciones (dl): nombre del dato y su valor --}}
            <dl class="datos">
                @if ($esAdopcion)
                    <div><dt>Sexo</dt><dd>{{ $publicacion->Sexo }}</dd></div>
                    <div><dt>Tamaño</dt><dd>{{ $adopcion->Tamaño }}</dd></div>
                    <div><dt>Esterilización</dt><dd>{{ $adopcion->Estado_Esterilizacion }}</dd></div>
                    <div><dt>Edad estimada</dt><dd>{{ $adopcion->edadTexto() }}</dd></div>
                    <div><dt>Ubicación aproximada</dt><dd>{{ $adopcion->Ubicacion_Aproximada }}</dd></div>
                @else
                    <div><dt>{{ $aviso->esEncontrada() ? 'Encontrado el' : 'Perdido el' }}</dt><dd>{{ date('d/m/Y', strtotime($aviso->Fecha_Suceso)) }}</dd></div>
                    <div><dt>Zona</dt><dd>{{ $aviso->Zona_Suceso }}</dd></div>
                    <div><dt>Publicado el</dt><dd>{{ date('d/m/Y', strtotime($publicacion->Fecha_Creacion)) }}</dd></div>
                @endif
                <div><dt>Publica</dt><dd>{{ $publicacion->publicanteVisible() }}</dd></div>
            </dl>

            @if ($esAdopcion)
                <div>
                    <h2 class="detalle-subtitulo">Estado de salud</h2>
                    <p>{{ $adopcion->Estado_Salud }}</p>
                </div>

                {{-- Solicitar adopción (RF:0049): el botón depende de quién mira la publicación --}}
                @if ($esPublicante)
                    {{-- El publicante ve sus solicitudes (RF:0050) --}}
                    <a class="btn btn-suave" href="/publicaciones/{{ $publicacion->ID_Publicacion }}/solicitudes">
                        Ver solicitudes recibidas ({{ $adopcion->solicitudes->count() }})
                    </a>
                @elseif ($adopcion->Estado_Adopcion == 'En adopción' && session('tipo') != 'Refugio' && session('tipo') != 'Administrador')
                    {{-- Pueden solicitar el Particular y el invitado (al tocar, el middleware lo manda a ingresar).
                         El Refugio no solicita adopciones (D49) y el Administrador tampoco: no ven el botón. --}}
                    @if ($solicitudPendiente)
                        <p class="apagado">Ya enviaste una solicitud para esta mascota. <a href="/solicitudes">Ver mis solicitudes</a></p>
                    @else
                        <a class="btn btn-primario detalle-contacto" href="/publicaciones/{{ $publicacion->ID_Publicacion }}/solicitar">Solicitar adopción</a>
                    @endif
                @endif
            @else
                <div>
                    <h2 class="detalle-subtitulo">Características</h2>
                    <p>{{ $aviso->Caracteristicas }}</p>
                </div>

                {{-- El contacto de una perdida o encontrada es público a propósito (13.2.2).
                     tel: hace que en el celular se pueda llamar con un toque. --}}
                <a class="btn btn-primario detalle-contacto" href="tel:{{ $aviso->Contacto }}">
                    Llamar al {{ $aviso->contactoFormateado() }}
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
