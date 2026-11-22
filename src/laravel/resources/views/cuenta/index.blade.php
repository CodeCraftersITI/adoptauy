{{-- Panel "Mi cuenta" del Particular y del Refugio. $nombre, $tipo, $publicaciones y $restringida llegan desde CuentaController. --}}
@extends('layouts.app')

@section('titulo', 'Mi cuenta')

@section('contenido')
<div class="seccion">
    <div class="seccion-cabecera">
        <div>
            <h1 class="pagina-titulo">Mi cuenta</h1>
            <p class="apagado">{{ $tipo }} · {{ $nombre }}</p>
        </div>
        <div class="nav-acciones-fila">
            {{-- Mis datos y contraseña (RF:0016, RF:0029, RF:0067) --}}
            <a class="btn btn-suave" href="/perfil">Mi perfil</a>
            {{-- Solicitudes (RF:0050, RF:0054): el Refugio no envía, solo recibe --}}
            @if ($tipo == 'Particular')
                <a class="btn btn-suave" href="/solicitudes">Mis solicitudes</a>
            @endif
            {{-- Ubicación en el mapa y cuentas para donaciones (RF:0035 a RF:0038) --}}
            @if ($tipo == 'Refugio')
                <a class="btn btn-suave" href="/mi-refugio">Mi refugio</a>
            @endif
            <a class="btn btn-suave" href="/solicitudes/recibidas">Solicitudes recibidas</a>
            {{-- Foto de seguimiento de las adopciones (RF:0056, RF:0057) --}}
            <a class="btn btn-suave" href="/seguimientos">Seguimientos</a>
            <a class="btn btn-primario" href="/publicar">Publicar mascota</a>
        </div>
    </div>

    {{-- RF:0058: con un seguimiento incumplido solo puede enviar la foto --}}
    @if ($restringida)
        <div class="mensaje mensaje-error">
            Tu cuenta está restringida: no podés solicitar adopciones ni publicar hasta que envíes
            la foto de seguimiento de tu adopción. <a href="/seguimientos">Enviar la foto</a>
        </div>
    @endif

    <h2 class="seccion-titulo">Mis publicaciones</h2>

    {{-- tabla-tarjetas: en el celular cada fila se muestra como una tarjeta; data-etiqueta es
         el nombre del dato que se ve adelante, porque ahí no está la fila de títulos --}}
    <div class="tarjeta tabla-caja">
        <table class="tabla tabla-tarjetas tabla-con-foto">
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
                        <td><a href="/publicaciones/{{ $publicacion->ID_Publicacion }}"><img class="miniatura" src="{{ $publicacion->primeraFoto() }}" alt="Ver la publicación"></a></td>
                        <td>
                            {{-- Las perdidas y encontradas se muestran por nombre; las de adopción, por raza --}}
                            @if ($publicacion->Tipo_Publicacion == 'Perdida')
                                <strong>{{ $publicacion->perdida->nombreVisible() }}</strong><br>
                            @endif
                            {{ $publicacion->Especie }} · {{ $publicacion->Raza }} · {{ $publicacion->Sexo }}
                        </td>
                        <td data-etiqueta="Tipo">{{ $publicacion->tipoVisible() }}</td>
                        <td data-etiqueta="Estado">
                            <span class="estado">{{ $publicacion->estado() }}</span>
                            @if ($publicacion->Tipo_Publicacion == 'Perdida')
                                <br><span class="apagado">Vence el {{ date('d/m/Y', strtotime($publicacion->perdida->Fecha_Vencimiento)) }}</span>
                            @endif
                        </td>
                        <td data-etiqueta="Publicada">{{ date('d/m/Y', strtotime($publicacion->Fecha_Creacion)) }}</td>
                        <td class="celda-acciones">
                            {{--
                                Acciones con íconos (corrección del docente: botones chicos). El nombre de
                                cada una aparece al pasar el mouse. Solo se ofrecen las transiciones válidas
                                según el estado (13.2.2): RF:0047 para perdidas y encontradas,
                                RF:0018, RF:0025 y RF:0045 para adopción. Adoptado y Resuelta son finales.
                            --}}
                            @php $id = $publicacion->ID_Publicacion; @endphp
                            <div class="acciones-iconos">
                                {{-- Editar (RF:0019, RF:0026) --}}
                                @if ($publicacion->sePuedeEditar())
                                    <a class="btn btn-suave btn-icono-chico" href="/publicaciones/{{ $id }}/editar" title="Editar" aria-label="Editar">
                                        <img class="icono" src="/img/editar.png" alt="">
                                    </a>
                                @endif

                                @if ($publicacion->Tipo_Publicacion == 'Perdida')
                                    @if (in_array($publicacion->perdida->Estado_Perdida, ['Vigente', 'Archivada']))
                                        @include('partials.boton-accion', ['url' => '/publicaciones/' . $id . '/renovar', 'icono' => 'renovar', 'texto' => 'Renovar 30 días', 'clase' => 'btn-suave'])
                                    @endif

                                    @if ($publicacion->perdida->Estado_Perdida == 'Vigente')
                                        @include('partials.boton-accion', ['url' => '/publicaciones/' . $id . '/resolver', 'icono' => 'resuelta', 'texto' => 'Marcar como resuelta', 'clase' => 'btn-primario',
                                            'confirmar' => '¿La mascota ya volvió con su familia? Esto no se puede deshacer.'])
                                        @include('partials.boton-accion', ['url' => '/publicaciones/' . $id . '/archivar', 'icono' => 'archivar', 'texto' => 'Archivar', 'clase' => 'btn-fantasma'])
                                    @endif
                                @else
                                    @if ($publicacion->adopcion->Estado_Adopcion == 'En adopción')
                                        @include('partials.boton-accion', ['url' => '/adopciones/' . $id . '/archivar', 'icono' => 'archivar', 'texto' => 'Archivar', 'clase' => 'btn-fantasma',
                                            'confirmar' => '¿Archivar la publicación? Deja de verse en el sitio, y la podés reactivar después.'])
                                        {{-- Con texto: solo con el ícono no se entendería --}}
                                        <a class="btn btn-suave btn-chico" href="/publicaciones/{{ $id }}/solicitudes">Ver solicitudes</a>
                                    @endif

                                    @if ($publicacion->adopcion->Estado_Adopcion == 'Archivado')
                                        @include('partials.boton-accion', ['url' => '/adopciones/' . $id . '/reactivar', 'icono' => 'reactivar', 'texto' => 'Reactivar', 'clase' => 'btn-suave'])
                                    @endif
                                @endif
                            </div>
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
