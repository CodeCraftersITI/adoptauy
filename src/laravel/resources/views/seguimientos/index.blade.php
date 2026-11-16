{{--
    Seguimiento posterior a la adopción (RF:0056, RF:0057, RF:0058).
    $comoAdoptante: mis adopciones, para enviar la foto.
    $comoPublicante: las adopciones de mis publicaciones, para revisar la foto.
    Ambos llegan desde SeguimientoController.
--}}
@extends('layouts.app')

@section('titulo', 'Seguimiento de adopciones')

@section('contenido')
<div class="seccion">
    <div class="seccion-cabecera">
        <h1 class="pagina-titulo">Seguimiento de adopciones</h1>
        <a class="btn btn-suave" href="/cuenta">Volver a Mi cuenta</a>
    </div>

    <p class="apagado">A los 15 días de la adopción, quien adoptó envía una foto del animal. Tiene 7 días para hacerlo.</p>

    {{-- ---------- Como adoptante: enviar la foto (RF:0056) ---------- --}}
    @if ($comoAdoptante->count() > 0)
        <h2 class="seccion-titulo">Mis adopciones</h2>

        <div class="tarjeta tabla-caja">
            <table class="tabla tabla-tarjetas">
                <thead>
                    <tr>
                        <th>Mascota</th>
                        <th>Adoptada</th>
                        <th>Plazo para la foto</th>
                        <th>Estado</th>
                        <th>Foto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comoAdoptante as $seguimiento)
                        @php $mascota = $seguimiento->adopcion->solicitud->adopcion->publicacion; @endphp
                        <tr>
                            <td>
                                <img class="miniatura" src="{{ $mascota->primeraFoto() }}" alt=""><br>
                                {{ $mascota->Especie }} · {{ $mascota->Raza }}
                            </td>
                            <td data-etiqueta="Adoptada">{{ date('d/m/Y', strtotime($seguimiento->adopcion->Fecha_Adopcion)) }}</td>
                            <td data-etiqueta="Plazo para la foto">
                                Del {{ date('d/m/Y', strtotime($seguimiento->Fecha_Aviso)) }}
                                al {{ date('d/m/Y', strtotime($seguimiento->Fecha_Limite)) }}
                                @if ($seguimiento->restringe())
                                    <br><span class="campo-error">Venció: tu cuenta está restringida hasta que la envíes.</span>
                                @endif
                            </td>
                            <td data-etiqueta="Estado"><span class="estado">{{ $seguimiento->Estado_Evidencia }}</span></td>
                            <td data-etiqueta="Foto">
                                @if ($seguimiento->puedeEnviarFoto())
                                    {{-- enctype="multipart/form-data" es obligatorio para enviar archivos --}}
                                    <form action="/seguimientos/{{ $seguimiento->ID_Adopcion }}/foto" method="POST" enctype="multipart/form-data" class="acciones-fila">
                                        @csrf
                                        @if ($seguimiento->Estado_Evidencia == 'Rechazada')
                                            <span class="apagado">La foto anterior fue rechazada: enviá otra.</span>
                                        @endif
                                        <input class="entrada" type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" required>
                                        @error('foto') <span class="campo-error">{{ $message }}</span> @enderror
                                        <button type="submit" class="btn btn-primario btn-chico">Enviar foto</button>
                                    </form>
                                @elseif ($seguimiento->Estado_Evidencia == 'Pendiente')
                                    <span class="apagado">La podés enviar desde el {{ date('d/m/Y', strtotime($seguimiento->Fecha_Aviso)) }}.</span>
                                @else
                                    <img class="miniatura" src="/storage/{{ $seguimiento->URL_Foto }}" alt="Foto enviada">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ---------- Como publicante: revisar la foto (RF:0057) ---------- --}}
    @if ($comoPublicante->count() > 0)
        <h2 class="seccion-titulo">Adopciones de mis publicaciones</h2>
        <p class="apagado">Si no revisás la foto, igual cuenta como presentada.</p>

        <div class="tarjeta tabla-caja">
            <table class="tabla tabla-tarjetas">
                <thead>
                    <tr>
                        <th>Mascota</th>
                        <th>Adoptante</th>
                        <th>Plazo para la foto</th>
                        <th>Estado</th>
                        <th>Foto</th>
                        <th>Revisar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comoPublicante as $seguimiento)
                        @php
                            $solicitud = $seguimiento->adopcion->solicitud;
                            $mascota = $solicitud->adopcion->publicacion;
                        @endphp
                        <tr>
                            <td>{{ $mascota->Especie }} · {{ $mascota->Raza }}</td>
                            <td data-etiqueta="Adoptante">{{ $solicitud->particular->Nombre }} {{ $solicitud->particular->Apellido }}</td>
                            <td data-etiqueta="Plazo para la foto">
                                Del {{ date('d/m/Y', strtotime($seguimiento->Fecha_Aviso)) }}
                                al {{ date('d/m/Y', strtotime($seguimiento->Fecha_Limite)) }}
                            </td>
                            <td data-etiqueta="Estado"><span class="estado">{{ $seguimiento->Estado_Evidencia }}</span></td>
                            <td data-etiqueta="Foto">
                                @if ($seguimiento->URL_Foto)
                                    <a href="/storage/{{ $seguimiento->URL_Foto }}" target="_blank">
                                        <img class="miniatura" src="/storage/{{ $seguimiento->URL_Foto }}" alt="Ver la foto">
                                    </a>
                                @else
                                    <span class="apagado">Todavía no la envió.</span>
                                @endif
                            </td>
                            <td class="celda-acciones">
                                {{-- Solo una foto Enviada se puede aprobar o rechazar --}}
                                @if ($seguimiento->Estado_Evidencia == 'Enviada')
                                    <div class="acciones-fila">
                                        <form action="/seguimientos/{{ $seguimiento->ID_Adopcion }}/aprobar" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-primario btn-chico">Aprobar</button>
                                        </form>
                                        <form action="/seguimientos/{{ $seguimiento->ID_Adopcion }}/rechazar" method="POST"
                                              onsubmit="return confirm('¿Rechazar la foto? El adoptante va a tener que enviar otra.')">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-fantasma btn-chico">Rechazar</button>
                                        </form>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($comoAdoptante->count() == 0 && $comoPublicante->count() == 0)
        <div class="tarjeta vacio">Todavía no tenés adopciones con seguimiento.</div>
    @endif
</div>
@endsection
