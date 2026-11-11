{{-- Mis solicitudes enviadas (RF:0054). $solicitudes llega desde SolicitudController. --}}
@extends('layouts.app')

@section('titulo', 'Mis solicitudes')

@section('contenido')
<div class="seccion">
    <div class="seccion-cabecera">
        <h1 class="pagina-titulo">Mis solicitudes</h1>
        <a class="btn btn-suave" href="/cuenta">Volver a Mi cuenta</a>
    </div>

    <p class="apagado">Quien publicó tiene tus datos y se comunica con vos por fuera de AdoptaUY.</p>

    <div class="tarjeta tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Mascota</th>
                    <th>Publica</th>
                    <th>Enviada</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($solicitudes as $solicitud)
                    @php $publicacion = $solicitud->adopcion->publicacion; @endphp
                    <tr>
                        <td><a href="/publicaciones/{{ $publicacion->ID_Publicacion }}"><img class="miniatura" src="{{ $publicacion->primeraFoto() }}" alt="Ver la publicación"></a></td>
                        <td>{{ $publicacion->Especie }} · {{ $publicacion->Raza }}<br>
                            <span class="apagado">{{ $solicitud->adopcion->Ubicacion_Aproximada }}</span></td>
                        <td>{{ $publicacion->publicanteVisible() }}</td>
                        <td>{{ date('d/m/Y', strtotime($solicitud->Fecha_Solicitud)) }}</td>
                        <td><span class="estado">{{ $solicitud->Estado_Solicitud }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="vacio">Todavía no enviaste ninguna solicitud. <a href="/adopcion">Ver mascotas en adopción</a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
