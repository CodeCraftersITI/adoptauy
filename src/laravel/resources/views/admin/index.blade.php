{{-- Panel de administración: solicitudes de registro de refugios (RF:0005, RF:0044). $pendientes llega desde AdminController. --}}
@extends('layouts.app')

@section('titulo', 'Panel de administración')

@section('contenido')
<div class="seccion">
    <h1 class="pagina-titulo">Panel de administración</h1>

    <h2 class="seccion-titulo">Solicitudes de refugio pendientes</h2>
    <p class="apagado">Cada solicitud se resuelve en un máximo de 5 días hábiles desde su fecha.</p>

    <div class="tarjeta tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Refugio</th>
                    <th>Contacto</th>
                    <th>Fecha de solicitud</th>
                    <th>Documentos</th>
                    <th>Resolver</th>
                </tr>
            </thead>
            <tbody>
                {{-- @forelse: si no hay solicitudes se muestra lo que está en @empty --}}
                @forelse ($pendientes as $refugio)
                    <tr>
                        <td>
                            <strong>{{ $refugio->Nombre_Refugio }}</strong><br>
                            <span class="apagado">{{ $refugio->Ubicacion }}</span>
                        </td>
                        <td>
                            {{ $refugio->usuario->Email }}<br>
                            @foreach ($refugio->contactos as $contacto)
                                <span class="apagado">{{ $contacto->Dato_Contacto }}</span><br>
                            @endforeach
                        </td>
                        <td>{{ date('d/m/Y H:i', strtotime($refugio->Fecha_Solicitud)) }}</td>
                        <td>
                            <a href="/admin/refugios/{{ $refugio->ID_Usuario }}/documento/certificado" target="_blank">Certificado</a><br>
                            <a href="/admin/refugios/{{ $refugio->ID_Usuario }}/documento/comprobante" target="_blank">Comprobante</a>
                        </td>
                        <td>
                            <div class="acciones-fila">
                                {{-- Los formularios solo envían GET o POST: @method('PUT') simula el PUT --}}
                                <form action="/admin/refugios/{{ $refugio->ID_Usuario }}/validar" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-primario btn-chico">Validar</button>
                                </form>

                                <form action="/admin/refugios/{{ $refugio->ID_Usuario }}/rechazar" method="POST" class="rechazo">
                                    @csrf
                                    @method('PUT')
                                    <textarea class="entrada" name="motivo" rows="2" maxlength="500" placeholder="Motivo del rechazo (obligatorio)" required></textarea>
                                    <button type="submit" class="btn btn-peligro btn-chico">Rechazar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="vacio">No hay solicitudes pendientes.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
