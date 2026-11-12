{{--
    Perfil público de un refugio validado (RF:0062): nombre, ubicación, contacto, cuentas bancarias
    para donaciones y sus mascotas publicadas. $refugio, $adopciones y $avisos llegan desde RefugioController.
--}}
@extends('layouts.app')

@section('titulo', $refugio->Nombre_Refugio)

@section('contenido')
<div class="seccion">
    <a class="detalle-volver" href="/refugios">← Volver a refugios</a>

    <div>
        <span class="estado">Refugio validado</span>
        <h1 class="pagina-titulo">{{ $refugio->Nombre_Refugio }}</h1>
    </div>

    <dl class="datos">
        <div><dt>Ubicación</dt><dd>{{ $refugio->Ubicacion }}</dd></div>
        <div>
            <dt>Contacto</dt>
            <dd>
                @foreach ($refugio->contactos as $contacto)
                    {{ $contacto->Dato_Contacto }}<br>
                @endforeach
            </dd>
        </div>
    </dl>

    {{-- Donaciones (RF:0062, D4): solo se muestran los datos; la plataforma no procesa pagos --}}
    <section class="seccion bloque-oscuro">
        <h2 class="seccion-titulo">Doná por transferencia</h2>
        <p>AdoptaUY no cobra comisión ni procesa pagos: la transferencia va directo a la cuenta del refugio.</p>

        @forelse ($refugio->cuentas as $cuenta)
            <dl class="datos datos-cuenta">
                <div><dt>Banco</dt><dd>{{ $cuenta->Banco }}</dd></div>
                <div><dt>Tipo de cuenta</dt><dd>{{ $cuenta->Tipo_Cuenta }}</dd></div>
                <div><dt>Número</dt><dd>{{ $cuenta->Numero_Cuenta }}</dd></div>
                <div><dt>Titular</dt><dd>{{ $cuenta->Titular }}</dd></div>
            </dl>
        @empty
            <p class="vacio">Este refugio todavía no publicó cuentas para donaciones. Podés comunicarte por su contacto.</p>
        @endforelse
    </section>

    <h2 class="seccion-titulo">En adopción en este refugio</h2>
    <div class="grilla-tarjetas">
        @forelse ($adopciones as $adopcion)
            @include('partials.tarjeta-adopcion', ['adopcion' => $adopcion])
        @empty
            <div class="tarjeta vacio">Este refugio no tiene mascotas en adopción en este momento.</div>
        @endforelse
    </div>

    @if ($avisos->count() > 0)
        <h2 class="seccion-titulo">Perdidas y encontradas</h2>
        <div class="grilla-tarjetas">
            @foreach ($avisos as $aviso)
                @include('partials.tarjeta-aviso', ['aviso' => $aviso])
            @endforeach
        </div>
    @endif
</div>
@endsection
