{{--
    Tarjeta de una mascota perdida o encontrada, que lleva a su detalle.
    Uso: @include('partials.tarjeta-aviso', ['aviso' => $perdida])
    Se usa en el inicio y en los listados /perdidas y /encontradas.
--}}
<a class="perdida mascota-enlace" href="/publicaciones/{{ $aviso->ID_Publicacion }}">
    <img class="mascota-foto" src="{{ $aviso->publicacion->primeraFoto() }}" alt="Foto de {{ $aviso->nombreVisible() }}">
    <span class="perdida-fecha">{{ $aviso->esEncontrada() ? 'Encontrado' : 'Perdido' }} el {{ date('d/m/Y', strtotime($aviso->Fecha_Suceso)) }} · {{ $aviso->Zona_Suceso }}</span>
    <strong class="perdida-nombre">{{ $aviso->nombreVisible() }}</strong>
    <span>{{ $aviso->publicacion->Especie }} · {{ $aviso->publicacion->Raza }} · {{ $aviso->publicacion->Sexo }}</span>
    <span>{{ $aviso->Caracteristicas }}</span>
    {{-- El contacto es público a propósito: lo cargó el publicante para que lo encuentren (13.2.2) --}}
    <span class="perdida-contacto">Contacto: {{ $aviso->contactoFormateado() }}</span>
</a>
