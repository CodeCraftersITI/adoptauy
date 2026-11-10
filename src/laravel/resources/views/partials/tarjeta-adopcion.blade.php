{{--
    Tarjeta de una mascota en adopción, que lleva a su detalle.
    Uso: @include('partials.tarjeta-adopcion', ['adopcion' => $adopcion])
    Se usa en el inicio y en el listado /adopcion.
--}}
<a class="tarjeta mascota mascota-enlace" href="/publicaciones/{{ $adopcion->ID_Publicacion }}">
    <img class="mascota-foto" src="{{ $adopcion->publicacion->primeraFoto() }}" alt="Foto de {{ $adopcion->publicacion->Raza }}">
    <div class="mascota-datos">
        <div class="mascota-titulo">
            <strong>{{ $adopcion->publicacion->Raza }}</strong>
            <span class="mascota-edad">{{ $adopcion->edadTexto() }}</span>
        </div>
        <span class="apagado">{{ $adopcion->publicacion->Especie }} · {{ $adopcion->publicacion->Sexo }} · {{ $adopcion->Tamaño }}</span>
        <span class="apagado">{{ $adopcion->Ubicacion_Aproximada }}</span>
    </div>
</a>
