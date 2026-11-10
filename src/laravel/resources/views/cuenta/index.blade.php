{{-- Panel "Mi cuenta" del Particular y del Refugio. $nombre, $tipo y $publicaciones llegan desde CuentaController. --}}
@extends('layouts.app')

@section('titulo', 'Mi cuenta')

@section('contenido')
<div class="seccion">
    <div class="seccion-cabecera">
        <div>
            <h1 class="pagina-titulo">Mi cuenta</h1>
            <p class="apagado">{{ $tipo }} · {{ $nombre }}</p>
        </div>
        <a class="btn btn-primario" href="/publicar/adopcion">Publicar mascota</a>
    </div>

    <h2 class="seccion-titulo">Mis publicaciones</h2>

    <div class="tarjeta tabla-caja">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Foto</th>
                    <th>Mascota</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Publicada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($publicaciones as $publicacion)
                    <tr>
                        <td><img class="miniatura" src="{{ $publicacion->primeraFoto() }}" alt=""></td>
                        <td>
                            {{-- Las perdidas y encontradas se muestran por nombre; las de adopción, por raza --}}
                            @if ($publicacion->Tipo_Publicacion == 'Perdida')
                                <strong>{{ $publicacion->perdida->nombreVisible() }}</strong><br>
                            @endif
                            {{ $publicacion->Especie }} · {{ $publicacion->Raza }} · {{ $publicacion->Sexo }}
                        </td>
                        <td>{{ $publicacion->tipoVisible() }}</td>
                        <td>
                            <span class="estado">{{ $publicacion->estado() }}</span>
                            @if ($publicacion->Tipo_Publicacion == 'Perdida')
                                <br><span class="apagado">Vence el {{ date('d/m/Y', strtotime($publicacion->perdida->Fecha_Vencimiento)) }}</span>
                            @endif
                        </td>
                        <td>{{ date('d/m/Y', strtotime($publicacion->Fecha_Creacion)) }}</td>
                        <td>
                            {{-- RF:0047: solo se ofrecen las transiciones válidas según el estado (13.2.2) --}}
                            @if ($publicacion->Tipo_Publicacion == 'Perdida')
                                <div class="acciones-fila">
                                    @if (in_array($publicacion->perdida->Estado_Perdida, ['Vigente', 'Archivada']))
                                        <form action="/publicaciones/{{ $publicacion->ID_Publicacion }}/renovar" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-suave btn-chico">Renovar 30 días</button>
                                        </form>
                                    @endif

                                    @if ($publicacion->perdida->Estado_Perdida == 'Vigente')
                                        <form action="/publicaciones/{{ $publicacion->ID_Publicacion }}/resolver" method="POST"
                                              onsubmit="return confirm('¿La mascota ya volvió con su familia? Esto no se puede deshacer.')">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-primario btn-chico">Marcar como resuelta</button>
                                        </form>

                                        <form action="/publicaciones/{{ $publicacion->ID_Publicacion }}/archivar" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-fantasma btn-chico">Archivar</button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="vacio">Todavía no publicaste ninguna mascota.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
