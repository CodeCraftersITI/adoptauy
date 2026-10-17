{{-- Panel "Mi cuenta" del Particular y del Refugio. $nombre y $tipo llegan desde CuentaController. --}}
@extends('layouts.app')

@section('titulo', 'Mi cuenta')

@section('contenido')
<div class="seccion">
    <div>
        <h1 class="pagina-titulo">Mi cuenta</h1>
        <p class="apagado">{{ $tipo }} · {{ $nombre }}</p>
    </div>

    <div class="tarjeta vacio">Acá vas a ver tus publicaciones, las solicitudes y el seguimiento.</div>
</div>
@endsection
