{{-- Sobre nosotros: qué es AdoptaUY. El crédito del equipo va en el pie de página. --}}
@extends('layouts.app')

@section('titulo', 'Sobre nosotros')

@section('contenido')
<div class="inicio">

    <section class="portada">
        <span class="etiqueta">Sobre nosotros</span>
        <h1>Queremos que cada perro y gato de Montevideo <span>encuentre su hogar.</span></h1>
        <p class="portada-texto">AdoptaUY reúne en un solo lugar las mascotas en adopción, las mascotas perdidas
            y los refugios de la ciudad. Acompañamos cada adopción con un seguimiento para que sea responsable.</p>
    </section>

    <section class="pasos">
        <div class="tarjeta paso">
            <strong>Nuestra misión</strong>
            <span class="apagado">Facilitar la adopción responsable conectando a quienes buscan un compañero con particulares y refugios.</span>
        </div>
        <div class="tarjeta paso">
            <strong>Cómo lo cuidamos</strong>
            <span class="apagado">Validamos la documentación de cada refugio, pedimos una foto a los 15 días de la adopción y revisamos los reportes de la comunidad.</span>
        </div>
        <div class="tarjeta paso">
            <strong>Sin fines de lucro</strong>
            <span class="apagado">La plataforma es gratuita: no cobra comisión ni procesa pagos. Las donaciones van directo a la cuenta de cada refugio.</span>
        </div>
    </section>

    <section class="seccion bloque-oscuro">
        <div class="seccion-cabecera">
            <h2 class="seccion-titulo">¿Tenés un refugio?</h2>
            <div class="nav-acciones-fila">
                <a class="btn btn-suave" href="/registro-refugio">Registrar un refugio</a>
                <a class="btn btn-primario" href="/#adopcion">Ver mascotas</a>
            </div>
        </div>
    </section>

</div>
@endsection
