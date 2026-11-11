{{--
    Solicitudes recibidas (RF:0050). $solicitudes llega desde SolicitudController.
    $publicacion es null en la lista general (/solicitudes/recibidas) o la publicación cuando es
    la lista de una sola (/publicaciones/{id}/solicitudes).
    Muestra teléfono y correo: solo la ven el publicante y el Administrador (RNF:0013).
--}}
@extends('layouts.app')

@section('titulo', 'Solicitudes recibidas')

@section('contenido')
<div class="seccion">
    <div class="seccion-cabecera">
        <div>
            <h1 class="pagina-titulo">Solicitudes recibidas</h1>
            @if ($publicacion)
                <p class="apagado">{{ $publicacion->Especie }} · {{ $publicacion->Raza }} · {{ $publicacion->adopcion->Ubicacion_Aproximada }}</p>
            @endif
        </div>
        @if ($publicacion)
            <a class="btn btn-suave" href="/publicaciones/{{ $publicacion->ID_Publicacion }}">Volver a la publicación</a>
        @else
            <a class="btn btn-suave" href="/cuenta">Volver a Mi cuenta</a>
        @endif
    </div>

    <p class="apagado">Comunicate con quien te interese por teléfono o correo. Ese contacto no se registra en AdoptaUY.</p>

    <div class="tarjeta tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    @if (!$publicacion)
                        <th>Mascota</th>
                    @endif
                    <th>Solicitante</th>
                    <th>Teléfono</th>
                    <th>Correo</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($solicitudes as $solicitud)
                    <tr>
                        @if (!$publicacion)
                            <td><a href="/publicaciones/{{ $solicitud->ID_Publicacion }}">{{ $solicitud->adopcion->publicacion->Especie }} · {{ $solicitud->adopcion->publicacion->Raza }}</a></td>
                        @endif
                        <td>{{ $solicitud->particular->Nombre }} {{ $solicitud->particular->Apellido }}</td>
                        <td><a href="tel:{{ $solicitud->particular->Telefono }}">{{ $solicitud->particular->Telefono }}</a></td>
                        <td><a href="mailto:{{ $solicitud->particular->usuario->Email }}">{{ $solicitud->particular->usuario->Email }}</a></td>
                        <td>{{ date('d/m/Y', strtotime($solicitud->Fecha_Solicitud)) }}</td>
                        <td><span class="estado">{{ $solicitud->Estado_Solicitud }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="vacio">Todavía no recibiste solicitudes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
