{{--
    Reportes pendientes en el panel del Administrador (RF:0060). Se resuelven por objeto:
    una decisión vale para todos sus reportes pendientes.
    $publicaciones y $usuarios llegan desde AdminReporteController.
--}}
@extends('layouts.app')

@section('titulo', 'Reportes · Administración')

@section('contenido')
<div class="seccion">
    <h1 class="pagina-titulo">Panel de administración</h1>
    @include('partials.menu-admin')

    <h2 class="seccion-titulo">Reportes pendientes</h2>
    <p class="apagado">Con 3 reportes una publicación se oculta y un refugio se suspende solos; un particular pasa a revisión con 5 y se inhabilita con 10. La medida automática queda hasta que la resuelvas.</p>

    {{-- ---------- Publicaciones ---------- --}}
    <h3 class="detalle-subtitulo">Publicaciones</h3>
    <div class="tarjeta tabla-caja">
        <table class="tabla tabla-tarjetas">
            <thead>
                <tr>
                    <th>Publicación</th>
                    <th>Estado</th>
                    <th>Motivos</th>
                    <th>Resolver</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($publicaciones as $publicacion)
                    <tr>
                        <td>
                            <a href="/publicaciones/{{ $publicacion->ID_Publicacion }}" target="_blank">
                                <img class="miniatura" src="{{ $publicacion->primeraFoto() }}" alt="Ver la publicación">
                            </a>
                            {{ $publicacion->tipoVisible() }}: {{ $publicacion->Especie }} · {{ $publicacion->Raza }}<br>
                            <span class="apagado">{{ $publicacion->usuario->nombreVisible() }}</span>
                        </td>
                        <td data-etiqueta="Estado"><span class="estado">{{ $publicacion->Estado_Moderacion }}</span></td>
                        <td data-etiqueta="Motivos">@include('admin.partials.motivos', ['reportes' => $publicacion->reportes()->where('Estado_Reporte', 'Pendiente')->get()])</td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                @foreach (['desestimar' => 'Desestimar', 'suspension' => 'Suspender publicación'] as $medida => $texto)
                                    <form action="/admin/reportes/publicacion/{{ $publicacion->ID_Publicacion }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="medida" value="{{ $medida }}">
                                        <button type="submit" class="btn btn-chico {{ $medida == 'desestimar' ? 'btn-suave' : 'btn-peligro' }}">{{ $texto }}</button>
                                    </form>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="vacio">No hay publicaciones con reportes pendientes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ---------- Particulares y refugios ---------- --}}
    <h3 class="detalle-subtitulo">Particulares y refugios</h3>
    <div class="tarjeta tabla-caja">
        <table class="tabla tabla-tarjetas">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Motivos</th>
                    <th>Resolver</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($usuarios as $usuario)
                    <tr>
                        <td>
                            <strong>{{ $usuario->nombreVisible() }}</strong><br>
                            <span class="apagado">{{ $usuario->Tipo_Usuario }} · {{ $usuario->Email }}</span>
                        </td>
                        <td data-etiqueta="Estado">
                            <span class="estado">{{ $usuario->Tipo_Usuario == 'Refugio' ? $usuario->refugio->Estado_Validacion : $usuario->Estado_Usuario }}</span>
                        </td>
                        <td data-etiqueta="Motivos">@include('admin.partials.motivos', ['reportes' => $usuario->reportesRecibidos()->where('Estado_Reporte', 'Pendiente')->get()])</td>
                        <td class="celda-acciones">
                            <div class="acciones-fila">
                                @foreach (['desestimar' => 'Desestimar', 'aviso' => 'Aviso', 'suspension' => 'Suspender', 'baja' => 'Dar de baja'] as $medida => $texto)
                                    <form action="/admin/reportes/usuario/{{ $usuario->ID_Usuario }}" method="POST"
                                          @if ($medida == 'baja') onsubmit="return confirm('¿Dar de baja esta cuenta? Es definitivo.')" @endif>
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="medida" value="{{ $medida }}">
                                        <button type="submit" class="btn btn-chico {{ in_array($medida, ['suspension', 'baja']) ? 'btn-peligro' : 'btn-suave' }}">{{ $texto }}</button>
                                    </form>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="vacio">No hay particulares ni refugios con reportes pendientes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
