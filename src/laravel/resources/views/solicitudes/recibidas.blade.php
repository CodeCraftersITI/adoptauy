{{--
    Solicitudes recibidas (RF:0050). $solicitudes llega desde SolicitudController.
    $publicacion es null en la lista general (/solicitudes/recibidas) o la publicación cuando es
    la lista de una sola (/publicaciones/{id}/solicitudes).
    Muestra teléfono y correo: solo la ven el publicante y el Administrador (RNF:0013).
    El publicante puede rechazar una solicitud (RF:0051) o elegir al adoptante (RF:0052);
    el Administrador solo las ve.
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

    <p class="apagado">Comunicate con quien te interese por teléfono o correo. Ese contacto no se registra en AdoptaUY.
        Cuando se concrete la adopción, elegí al adoptante: las demás solicitudes se rechazan solas.</p>

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
                    <th>Acciones</th>
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
                        <td>
                            {{-- Solo el publicante, y solo sobre una Pendiente de una mascota En adopción (13.2.3) --}}
                            @if ($solicitud->Estado_Solicitud == 'Pendiente'
                                && $solicitud->adopcion->Estado_Adopcion == 'En adopción'
                                && $solicitud->adopcion->publicacion->ID_Usuario == session('id_usuario'))
                                <div class="acciones-fila">
                                    {{-- confirm(): elegir es definitivo, se pide confirmación antes de enviar --}}
                                    <form action="/solicitudes/{{ $solicitud->ID_Solicitud }}/elegir" method="POST"
                                          onsubmit="return confirm('¿Elegir a este solicitante como adoptante? Es definitivo: la mascota pasa a Adoptado y las demás solicitudes se rechazan.')">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-primario btn-chico">Elegir como adoptante</button>
                                    </form>

                                    <form action="/solicitudes/{{ $solicitud->ID_Solicitud }}/rechazar" method="POST"
                                          onsubmit="return confirm('¿Rechazar esta solicitud?')">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-fantasma btn-chico">Rechazar</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="vacio">Todavía no recibiste solicitudes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
