{{--
    Particulares en el panel del Administrador: listado (RF:0014) y baja lógica (RF:0010).
    La CI no se muestra (RNF:0013). $particulares llega paginado desde AdminParticularController.
--}}
@extends('layouts.app')

@section('titulo', 'Particulares · Administración')

@section('contenido')
<div class="seccion">
    <h1 class="pagina-titulo">Panel de administración</h1>
    @include('partials.menu-admin')

    <div class="seccion-cabecera">
        <h2 class="seccion-titulo">Particulares</h2>
        <span class="apagado">{{ $particulares->total() }} {{ $particulares->total() == 1 ? 'particular' : 'particulares' }}</span>
    </div>

    <form action="/admin/particulares" method="GET" class="filtros">
        <label class="campo">
            Buscar
            <input class="entrada" type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, apellido o correo">
        </label>

        <label class="campo">
            Estado
            <select class="entrada" name="estado">
                <option value="">Todos</option>
                @foreach (['Activo', 'En revisión', 'Suspendido', 'Inhabilitado', 'Baja'] as $estado)
                    <option value="{{ $estado }}" {{ request('estado') == $estado ? 'selected' : '' }}>{{ $estado }}</option>
                @endforeach
            </select>
        </label>

        <div class="filtros-botones">
            <button type="submit" class="btn btn-primario">Filtrar</button>
            <a class="btn btn-fantasma" href="/admin/particulares">Limpiar</a>
        </div>
    </form>

    <div class="tarjeta tabla-caja">
        <table class="tabla tabla-tarjetas">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Contacto</th>
                    <th>Alta</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($particulares as $particular)
                    <tr>
                        <td><strong>{{ $particular->Nombre }} {{ $particular->Apellido }}</strong></td>
                        <td data-etiqueta="Contacto">
                            {{ $particular->usuario->Email }}<br>
                            <span class="apagado">{{ $particular->Telefono }}</span>
                        </td>
                        <td data-etiqueta="Alta">{{ date('d/m/Y', strtotime($particular->usuario->Fecha_Alta)) }}</td>
                        <td data-etiqueta="Estado"><span class="estado">{{ $particular->usuario->Estado_Usuario }}</span></td>
                        <td class="celda-acciones">
                            {{-- Editar cédula, nombre, apellido y teléfono (RF:0013) --}}
                            <a class="btn btn-suave btn-chico" href="/admin/particulares/{{ $particular->ID_Usuario }}/editar">Editar</a>
                            {{-- Restituir: después de una revisión, suspensión o inhabilitación por reportes (RF:0060) --}}
                            @if (in_array($particular->usuario->Estado_Usuario, ['En revisión', 'Suspendido', 'Inhabilitado']))
                                <form action="/admin/particulares/{{ $particular->ID_Usuario }}/restituir" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-suave btn-chico">Restituir</button>
                                </form>
                            @endif
                            @if ($particular->usuario->Estado_Usuario != 'Baja')
                                <form action="/admin/particulares/{{ $particular->ID_Usuario }}/baja" method="POST"
                                      onsubmit="return confirm('¿Dar de baja esta cuenta? No va a poder volver a iniciar sesión.')">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit" class="btn btn-peligro btn-chico">Dar de baja</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="vacio">No hay particulares que cumplan esos filtros.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('partials.paginacion', ['lista' => $particulares])
</div>
@endsection
