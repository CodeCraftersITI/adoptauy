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
            <a class="tarjeta acceso" href="/#adopcion">
                <span class="acceso-icono">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>
                </span>
                <strong>Quiero adoptar</strong>
                <span class="apagado">Filtrá por especie, edad, raza y sexo.</span>
            </a>
            <a class="tarjeta acceso" href="/#perdidas">
                <span class="acceso-icono">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                </span>
                <strong>Perdí o encontré una</strong>
                <span class="apagado">Publicación gratuita, visible para todos.</span>
            </a>
            <a class="tarjeta acceso" href="/nosotros">
                <span class="acceso-icono">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 10l9-6 9 6M5 10v9h14v-9M10 19v-5h4v5"/></svg>
                </span>
                <strong>Ayudar a un refugio</strong>
                <span class="apagado">Donaciones por transferencia, sin comisión.</span>
            </a>
        </div>
    </section>

    {{-- RF:0040: últimas publicaciones En adopción ($adopciones llega desde InicioController) --}}
    <section class="seccion" id="adopcion">
        <h2 class="seccion-titulo">Recién publicados</h2>

        <div class="grilla-tarjetas">
            @forelse ($adopciones as $adopcion)
                <article class="tarjeta mascota">
                    <img class="mascota-foto" src="{{ $adopcion->publicacion->primeraFoto() }}" alt="Foto de {{ $adopcion->publicacion->Raza }}">
                    <div class="mascota-datos">
                        <div class="mascota-titulo">
                            <strong>{{ $adopcion->publicacion->Raza }}</strong>
                            <span class="mascota-edad">{{ $adopcion->edadTexto() }}</span>
                        </div>
                        <span class="apagado">{{ $adopcion->publicacion->Especie }} · {{ $adopcion->publicacion->Sexo }} · {{ $adopcion->Tamaño }}</span>
                        <span class="apagado">{{ $adopcion->Ubicacion_Aproximada }}</span>
                    </div>
                </article>
            @empty
                <div class="tarjeta vacio">Todavía no hay mascotas en adopción.</div>
            @endforelse
        </div>
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

    {{-- RF:0048: mascotas en estado Perdida y vigentes ($perdidas llega desde InicioController) --}}
    <section class="seccion bloque-oscuro" id="perdidas">
        <div class="seccion-cabecera">
            <h2 class="seccion-titulo">Mascotas perdidas</h2>
            <a class="btn btn-suave" href="/publicar/perdida">Publicar perdida</a>
        </div>

        <div class="grilla-tarjetas">
            @forelse ($perdidas as $perdida)
                <article class="perdida">
                    <img class="mascota-foto" src="{{ $perdida->publicacion->primeraFoto() }}" alt="Foto de {{ $perdida->Nombre_Mascota }}">
                    <span class="perdida-fecha">Perdido el {{ date('d/m/Y', strtotime($perdida->Fecha_Extravio)) }} · {{ $perdida->Zona_Extravio }}</span>
                    <strong class="perdida-nombre">{{ $perdida->Nombre_Mascota }}</strong>
                    <span>{{ $perdida->publicacion->Especie }} · {{ $perdida->publicacion->Raza }} · {{ $perdida->publicacion->Sexo }}</span>
                    <span>{{ $perdida->Caracteristicas }}</span>
                    {{-- El contacto es público a propósito: lo cargó el publicante para que lo encuentren (13.2.2) --}}
                    <span class="perdida-contacto">Contacto: {{ $perdida->Contacto }}</span>
                </article>
            @empty
                <div class="vacio">Todavía no hay mascotas perdidas publicadas.</div>
            @endforelse
        </div>
    </section>

</div>
@endsection
