{{--
    Página que muestra Laravel cuando se supera el límite de envíos por minuto (middleware throttle).
    El código 429 significa "demasiadas solicitudes".
--}}
@extends('layouts.app')

@section('titulo', 'Demasiados intentos')

@section('contenido')
<div class="formulario-angosto">
    <h1 class="pagina-titulo">Demasiados intentos</h1>

    <div class="tarjeta formulario">
        <p>Enviaste este formulario muchas veces en poco tiempo. Esperá un minuto y volvé a intentarlo.</p>
        <a class="btn btn-primario" href="/">Volver al inicio</a>
    </div>
</div>
@endsection
