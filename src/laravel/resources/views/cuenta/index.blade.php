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
                </tr>
            </thead>
            <tbody>
                @forelse ($publicaciones as $publicacion)
                    <tr>
                        <td><img class="miniatura" src="{{ $publicacion->primeraFoto() }}" alt=""></td>
                        <td>
                            {{-- Las perdidas tienen nombre; las de adopción se muestran por raza --}}
                            @if ($publicacion->Tipo_Publicacion == 'Perdida')
                                <strong>{{ $publicacion->perdida->Nombre_Mascota }}</strong><br>
                            @endif
                            {{ $publicacion->Especie }} · {{ $publicacion->Raza }} · {{ $publicacion->Sexo }}
                        </td>
                        <td>{{ $publicacion->Tipo_Publicacion }}</td>
                        <td>
                            <span class="estado">{{ $publicacion->estado() }}</span>
                            @if ($publicacion->Tipo_Publicacion == 'Perdida')
                                <br><span class="apagado">Vence el {{ date('d/m/Y', strtotime($publicacion->perdida->Fecha_Vencimiento)) }}</span>
                            @endif
                        </td>
                        <td>{{ date('d/m/Y', strtotime($publicacion->Fecha_Creacion)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="vacio">Todavía no publicaste ninguna mascota.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
