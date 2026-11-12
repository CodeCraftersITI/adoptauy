{{--
    Refugios en el panel del Administrador: listado y consulta (RF:0008, RF:0009),
    suspensión y baja lógica (RF:0006, tabla de 13.2.1).
    $refugios llega paginado desde AdminRefugioController.
--}}
@extends('layouts.app')

@section('titulo', 'Refugios · Administración')

@section('contenido')
<div class="seccion">
    <h1 class="pagina-titulo">Panel de administración</h1>
    @include('partials.menu-admin')

    <div class="seccion-cabecera">
        <h2 class="seccion-titulo">Refugios</h2>
        <span class="apagado">{{ $refugios->total() }} {{ $refugios->total() == 1 ? 'refugio' : 'refugios' }}</span>
    </div>

    <form action="/admin/refugios" method="GET" class="filtros">
        <label class="campo">
            Nombre
            <input class="entrada" type="text" name="nombre" value="{{ request('nombre') }}">
        </label>

        <label class="campo">
            Ubicación
            <input class="entrada" type="text" name="ubicacion" value="{{ request('ubicacion') }}" placeholder="Barrio o dirección">
        </label>

        <label class="campo">
            Estado
            <select class="entrada" name="estado">
                <option value="">Todos</option>
                @foreach (['Pendiente de validación', 'Validado', 'Rechazado', 'Suspendido', 'Dado de baja'] as $estado)
                    <option value="{{ $estado }}" {{ request('estado') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                @endforeach
            </select>
        </label>

        <div class="filtros-botones">
            <button type="submit" class="btn btn-primario">Filtrar</button>
            <a class="btn btn-fantasma" href="/admin/refugios">Limpiar</a>
        </div>
    </form>

    <div class="tarjeta tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Refugio</th>
                    <th>Contacto</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($refugios as $refugio)
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
                        <td><span class="estado">{{ $refugio->Estado_Validacion }}</span></td>
                        <td>
                            {{-- Solo se ofrecen las transiciones válidas según el estado (13.2.1) --}}
                            <div class="acciones-fila">
                                @if ($refugio->Estado_Validacion == 'Validado')
                                    <a class="btn btn-suave btn-chico" href="/refugios/{{ $refugio->ID_Usuario }}">Ver perfil</a>
                                    <form action="/admin/refugios/{{ $refugio->ID_Usuario }}/suspender" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-suave btn-chico">Suspender</button>
                                    </form>
                                @endif

                                @if ($refugio->Estado_Validacion == 'Suspendido')
                                    <form action="/admin/refugios/{{ $refugio->ID_Usuario }}/levantar" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-primario btn-chico">Levantar suspensión</button>
                                    </form>
                                @endif

                                @if (in_array($refugio->Estado_Validacion, ['Validado', 'Suspendido']))
                                    {{-- confirm(): la baja es definitiva, se pide confirmación antes de enviar --}}
                                    <form action="/admin/refugios/{{ $refugio->ID_Usuario }}/baja" method="POST"
                                          onsubmit="return confirm('¿Dar de baja a este refugio? Es definitivo y sus publicaciones se archivan.')">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-peligro btn-chico">Dar de baja</button>
                                    </form>
                                @endif

                                @if ($refugio->Estado_Validacion == 'Pendiente de validación')
                                    <a class="btn btn-suave btn-chico" href="/admin">Resolver solicitud</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="vacio">No hay refugios que cumplan esos filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.paginacion', ['lista' => $refugios])
</div>
@endsection
