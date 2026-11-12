{{--
    Publicaciones en el panel del Administrador: listado y consulta con filtros
    (RF:0002, RF:0004, RF:0011, RF:0012) y baja (RF:0001).
    $publicaciones llega paginado desde AdminPublicacionController.
--}}
@extends('layouts.app')

@section('titulo', 'Publicaciones · Administración')

@section('contenido')
<div class="seccion">
    <h1 class="pagina-titulo">Panel de administración</h1>
    @include('partials.menu-admin')

    <div class="seccion-cabecera">
        <h2 class="seccion-titulo">Publicaciones</h2>
        <span class="apagado">{{ $publicaciones->total() }} {{ $publicaciones->total() == 1 ? 'publicación' : 'publicaciones' }}</span>
    </div>

    <form action="/admin/publicaciones" method="GET" class="filtros">
        <label class="campo">
            Tipo
            <select class="entrada" name="tipo">
                <option value="">Todos</option>
                <option value="Adopción" {{ request('tipo') == 'Adopción' ? 'selected' : '' }}>Adopción</option>
                <option value="Perdida" {{ request('tipo') == 'Perdida' ? 'selected' : '' }}>Perdida</option>
                <option value="Encontrada" {{ request('tipo') == 'Encontrada' ? 'selected' : '' }}>Encontrada</option>
            </select>
        </label>

        <label class="campo">
            Especie
            <select class="entrada" name="especie">
                <option value="">Todas</option>
                <option value="Perro" {{ request('especie') == 'Perro' ? 'selected' : '' }}>Perro</option>
                <option value="Gato" {{ request('especie') == 'Gato' ? 'selected' : '' }}>Gato</option>
            </select>
        </label>

        <label class="campo">
            Publicante
            <select class="entrada" name="publicante">
                <option value="">Todos</option>
                <option value="Particular" {{ request('publicante') == 'Particular' ? 'selected' : '' }}>Particulares</option>
                <option value="Refugio" {{ request('publicante') == 'Refugio' ? 'selected' : '' }}>Refugios</option>
            </select>
        </label>

        <label class="campo">
            Usuario
            <input class="entrada" type="text" name="usuario" value="{{ request('usuario') }}" placeholder="Nombre o correo">
        </label>

        <label class="campo">
            Desde
            <input class="entrada" type="date" name="desde" value="{{ request('desde') }}">
        </label>

        <label class="campo">
            Hasta
            <input class="entrada" type="date" name="hasta" value="{{ request('hasta') }}">
        </label>

        <div class="filtros-botones">
            <button type="submit" class="btn btn-primario">Filtrar</button>
            <a class="btn btn-fantasma" href="/admin/publicaciones">Limpiar</a>
        </div>
    </form>

    <div class="tarjeta tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Mascota</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Publicante</th>
                    <th>Publicada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($publicaciones as $publicacion)
                    <tr>
                        <td><a href="/publicaciones/{{ $publicacion->ID_Publicacion }}"><img class="miniatura" src="{{ $publicacion->primeraFoto() }}" alt="Ver la publicación"></a></td>
                        <td>{{ $publicacion->Especie }} · {{ $publicacion->Raza }} · {{ $publicacion->Sexo }}</td>
                        <td>{{ $publicacion->tipoVisible() }}</td>
                        <td>
                            <span class="estado">{{ $publicacion->estado() }}</span>
                            @if ($publicacion->Estado_Moderacion != 'Visible')
                                <br><span class="apagado">{{ $publicacion->Estado_Moderacion }}</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $publicacion->usuario->nombreVisible() }}</strong><br>
                            <span class="apagado">{{ $publicacion->usuario->Tipo_Usuario }} · {{ $publicacion->usuario->Email }}</span>
                        </td>
                        <td>{{ date('d/m/Y H:i', strtotime($publicacion->Fecha_Creacion)) }}</td>
                        <td>
                            {{-- Solo se ofrece la baja si la transición es válida (13.2.2) --}}
                            @if (in_array($publicacion->estado(), ['En adopción', 'Vigente']))
                                <form action="/admin/publicaciones/{{ $publicacion->ID_Publicacion }}/archivar" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-peligro btn-chico">Dar de baja</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="vacio">No hay publicaciones que cumplan esos filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.paginacion', ['lista' => $publicaciones])
</div>
@endsection
