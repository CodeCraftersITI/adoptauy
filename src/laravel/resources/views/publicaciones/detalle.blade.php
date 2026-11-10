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
        {{--
            Carrusel de fotos: una foto a la vez, con flechas, contador y miniaturas.
            Las fotos van en fila dentro de "carrusel-pista"; el CSS (scroll-snap) hace que se
            deslicen de a una (también con el dedo en el celular) y el script de abajo mueve
            la pista con las flechas y las miniaturas.
        --}}
        @php $totalFotos = $publicacion->fotos->count(); @endphp
        <div class="detalle-fotos">
            <div class="carrusel">
                <div class="carrusel-pista" id="carrusel-pista">
                    @foreach ($publicacion->fotos as $foto)
                        <img class="carrusel-foto" src="/storage/{{ $foto->URL_Foto }}" alt="Foto {{ $loop->iteration }} de {{ $totalFotos }}">
                    @endforeach
                </div>

                @if ($totalFotos > 1)
                    <button type="button" class="carrusel-flecha carrusel-anterior" id="foto-anterior" aria-label="Foto anterior">‹</button>
                    <button type="button" class="carrusel-flecha carrusel-siguiente" id="foto-siguiente" aria-label="Foto siguiente">›</button>
                    <span class="carrusel-contador"><span id="foto-actual">1</span> / {{ $totalFotos }}</span>
                @endif
            </div>

            @if ($totalFotos > 1)
                <div class="detalle-miniaturas">
                    @foreach ($publicacion->fotos as $foto)
                        <button type="button" class="miniatura-boton {{ $loop->first ? 'miniatura-activa' : '' }}" data-indice="{{ $loop->index }}" aria-label="Ver la foto {{ $loop->iteration }}">
                            <img src="/storage/{{ $foto->URL_Foto }}" alt="">
                        </button>
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

{{-- Carrusel: solo hace falta si hay más de una foto --}}
@if ($totalFotos > 1)
<script>
    const pista = document.getElementById('carrusel-pista');
    const miniaturas = document.querySelectorAll('.miniatura-boton');
    const total = {{ $totalFotos }};
    let actual = 0;   // número de la foto que se ve (0 es la primera)

    // Mueve la pista hasta la foto pedida. Desde la última, "siguiente" vuelve a la primera.
    function mostrarFoto(indice)
    {
        actual = (indice + total) % total;
        pista.scrollTo({ left: pista.clientWidth * actual, behavior: 'smooth' });
    }

    // Actualiza el contador "2 / 3" y marca la miniatura de la foto que se ve
    function actualizarIndicadores()
    {
        document.getElementById('foto-actual').textContent = actual + 1;
        miniaturas.forEach(function (boton, i)
        {
            boton.classList.toggle('miniatura-activa', i === actual);
        });
    }

    document.getElementById('foto-anterior').addEventListener('click', function ()
    {
        mostrarFoto(actual - 1);
    });

    document.getElementById('foto-siguiente').addEventListener('click', function ()
    {
        mostrarFoto(actual + 1);
    });

    miniaturas.forEach(function (boton)
    {
        boton.addEventListener('click', function ()
        {
            mostrarFoto(Number(boton.dataset.indice));
        });
    });

    // Si la persona desliza con el dedo, se calcula qué foto quedó a la vista
    pista.addEventListener('scroll', function ()
    {
        actual = Math.round(pista.scrollLeft / pista.clientWidth);
        actualizarIndicadores();
    });
</script>
@endif
@endsection
