{{--
    Página de inicio pública (RF:0040, RF:0048).
    Por ahora es estática: los listados se llenan cuando existan los modelos de publicaciones.
--}}
@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
<div class="inicio">

    <section class="portada">
        <h1>Adoptar cambia dos vidas. <span>La tuya también.</span></h1>

        <div class="accesos">
            <a class="tarjeta acceso" href="#">
                <span class="acceso-icono">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>
                </span>
                <strong>Quiero adoptar</strong>
                <span class="apagado">Filtrá por especie, edad, raza y sexo.</span>
            </a>
            <a class="tarjeta acceso" href="#">
                <span class="acceso-icono">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                </span>
                <strong>Perdí o encontré una</strong>
                <span class="apagado">Publicación gratuita, visible para todos.</span>
            </a>
            <a class="tarjeta acceso" href="#">
                <span class="acceso-icono">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 10l9-6 9 6M5 10v9h14v-9M10 19v-5h4v5"/></svg>
                </span>
                <strong>Ayudar a un refugio</strong>
                <span class="apagado">Donaciones por transferencia, sin comisión.</span>
            </a>
        </div>
    </section>

    {{-- RF:0040: últimas publicaciones En adopción --}}
    <section class="seccion">
        <h2 class="seccion-titulo">Recién publicados</h2>
        <div class="tarjeta vacio">Todavía no hay mascotas en adopción.</div>
    </section>

    <section class="seccion">
        <h2 class="seccion-titulo">Cómo funciona</h2>
        <ol class="pasos">
            <li class="tarjeta paso">
                <span class="paso-numero">1</span>
                <strong>Enviás la solicitud</strong>
                <span class="apagado">Aceptás compartir tu nombre, teléfono y correo con quien publicó.</span>
            </li>
            <li class="tarjeta paso">
                <span class="paso-numero">2</span>
                <strong>Te contactan</strong>
                <span class="apagado">El publicante recibe un correo y se comunica con vos.</span>
            </li>
            <li class="tarjeta paso">
                <span class="paso-numero">3</span>
                <strong>Seguimiento a los 15 días</strong>
                <span class="apagado">Te pedimos una foto del animal. Tenés 7 días para enviarla.</span>
            </li>
        </ol>
    </section>

    {{-- RF:0048: mascotas en estado Perdida --}}
    <section class="seccion bloque-oscuro">
        <div class="seccion-cabecera">
            <h2 class="seccion-titulo">Mascotas perdidas</h2>
        </div>
        <div class="vacio">Todavía no hay mascotas perdidas publicadas.</div>
    </section>

</div>
@endsection
