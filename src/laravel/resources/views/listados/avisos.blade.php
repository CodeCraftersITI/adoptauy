{{--
    Listado de mascotas perdidas (RF:0048) o encontradas (RF:0065): misma vista para los dos.
    $avisos (paginado), $tipo, $titulo y $descripcion llegan desde ListadoController.
--}}
@extends('layouts.app')

@section('titulo', $titulo)

@section('contenido')
<div class="seccion bloque-oscuro">
    <div class="seccion-cabecera">
        <div>
            <h1 class="seccion-titulo">{{ $titulo }}</h1>
            <p>{{ $descripcion }}</p>
        </div>
        <a class="btn btn-suave" href="/publicar/{{ $tipo == 'Perdida' ? 'perdida' : 'encontrada' }}">
            {{ $tipo == 'Perdida' ? 'Publicar perdida' : 'Publicar encontrada' }}
        </a>
    </div>

    <div class="grilla-tarjetas">
        @forelse ($avisos as $aviso)
            @include('partials.tarjeta-aviso', ['aviso' => $aviso])
        @empty
            <div class="vacio">Por ahora no hay publicaciones vigentes.</div>
        @endforelse
    </div>

    @include('partials.paginacion', ['lista' => $avisos])
</div>
@endsection
