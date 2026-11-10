{{--
    Listado de mascotas en adopción con filtros (RF:0040, RF:0041).
    $adopciones llega paginado desde ListadoController.
    El formulario usa GET: los filtros quedan en la URL (/adopcion?especie=Perro&edad=cachorro).
--}}
@extends('layouts.app')

@section('titulo', 'Mascotas en adopción')

@section('contenido')
<div class="seccion">
    <div class="seccion-cabecera">
        <h1 class="pagina-titulo">Mascotas en adopción</h1>
        <span class="apagado">{{ $adopciones->total() }} {{ $adopciones->total() == 1 ? 'mascota' : 'mascotas' }}</span>
    </div>

    {{-- Cada filtro es un control propio y se combinan entre sí (RF:0021) --}}
    <form action="/adopcion" method="GET" class="filtros">
        <label class="campo">
            Especie
            <select class="entrada" name="especie">
                <option value="">Todas</option>
                <option value="Perro" {{ request('especie') == 'Perro' ? 'selected' : '' }}>Perro</option>
                <option value="Gato" {{ request('especie') == 'Gato' ? 'selected' : '' }}>Gato</option>
            </select>
        </label>

        <label class="campo">
            Sexo
            <select class="entrada" name="sexo">
                <option value="">Todos</option>
                <option value="Hembra" {{ request('sexo') == 'Hembra' ? 'selected' : '' }}>Hembra</option>
                <option value="Macho" {{ request('sexo') == 'Macho' ? 'selected' : '' }}>Macho</option>
            </select>
        </label>

        <label class="campo">
            Edad <span class="campo-ayuda">(cachorro: menos de 1 año)</span>
            <select class="entrada" name="edad">
                <option value="">Todas</option>
                <option value="cachorro" {{ request('edad') == 'cachorro' ? 'selected' : '' }}>Cachorros</option>
                <option value="adulto" {{ request('edad') == 'adulto' ? 'selected' : '' }}>Adultos</option>
            </select>
        </label>

        <label class="campo">
            Raza
            <input class="entrada" type="text" name="raza" value="{{ request('raza') }}" placeholder="Ej.: mestizo">
        </label>

        <label class="campo">
            Publicadas
            <select class="entrada" name="fecha">
                <option value="">Cualquier fecha</option>
                <option value="7" {{ request('fecha') == '7' ? 'selected' : '' }}>Últimos 7 días</option>
                <option value="30" {{ request('fecha') == '30' ? 'selected' : '' }}>Últimos 30 días</option>
            </select>
        </label>

        <div class="filtros-botones">
            <button type="submit" class="btn btn-primario">Filtrar</button>
            <a class="btn btn-fantasma" href="/adopcion">Limpiar</a>
        </div>
    </form>

    <div class="grilla-tarjetas">
        @forelse ($adopciones as $adopcion)
            @include('partials.tarjeta-adopcion', ['adopcion' => $adopcion])
        @empty
            <div class="tarjeta vacio">No hay mascotas en adopción que cumplan esos filtros.</div>
        @endforelse
    </div>

    @include('partials.paginacion', ['lista' => $adopciones])
</div>
@endsection
