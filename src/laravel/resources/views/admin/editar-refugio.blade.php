{{--
    El Administrador modifica un Refugio (RF:0007): nombre, datos de contacto y ubicación, con el
    mismo partial que "Mi perfil". El estado de validación se cambia con las transiciones válidas
    de 13.2.1 (validar, rechazar, suspender, levantar la suspensión, dar de baja).
    $refugio llega desde AdminRefugioController.
--}}
@extends('layouts.app')

@section('titulo', 'Editar refugio · Administración')

@section('contenido')
<div class="formulario-angosto formulario-ancho">
    <a class="detalle-volver" href="/admin/refugios">← Volver a Refugios</a>

    <h1 class="pagina-titulo">Editar refugio</h1>

    <form action="/admin/refugios/{{ $refugio->ID_Usuario }}" method="POST" class="tarjeta formulario formulario-grilla">
        @csrf
        @method('PUT')

        <dl class="datos ocupa-todo">
            <div><dt>Correo</dt><dd>{{ $refugio->usuario->Email }}</dd></div>
            <div><dt>Estado de validación</dt><dd>{{ $refugio->Estado_Validacion }}</dd></div>
        </dl>

        @include('partials.campos-refugio', ['refugio' => $refugio])

        <button type="submit" class="btn btn-primario ocupa-todo">Guardar cambios</button>
    </form>

    {{-- Estado de validación (RF:0007): solo las transiciones válidas según el estado actual (13.2.1) --}}
    <div class="tarjeta formulario">
        <h2 class="detalle-subtitulo">Estado de validación: {{ $refugio->Estado_Validacion }}</h2>

        <div class="nav-acciones-fila">
            @if ($refugio->Estado_Validacion == 'Pendiente de validación')
                <a class="btn btn-suave btn-chico" href="/admin">Validar o rechazar la solicitud</a>
            @endif

            @if ($refugio->Estado_Validacion == 'Validado')
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
                <form action="/admin/refugios/{{ $refugio->ID_Usuario }}/baja" method="POST"
                      onsubmit="return confirm('¿Dar de baja a este refugio? Es definitivo y sus publicaciones se archivan.')">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn btn-peligro btn-chico">Dar de baja</button>
                </form>
            @endif

            @if (in_array($refugio->Estado_Validacion, ['Rechazado', 'Dado de baja']))
                <span class="apagado">Es un estado final: no tiene más transiciones.</span>
            @endif
        </div>
    </div>
</div>
@endsection
