{{--
    Enlaces de página anterior y siguiente para una lista hecha con paginate().
    Uso: @include('partials.paginacion', ['lista' => $adopciones])
    Si entra todo en una sola página, no muestra nada.
--}}
@if ($lista->hasPages())
    <nav class="paginacion" aria-label="Páginas">
        @if ($lista->onFirstPage())
            <span class="btn btn-suave btn-chico deshabilitado">← Anterior</span>
        @else
            <a class="btn btn-suave btn-chico" href="{{ $lista->previousPageUrl() }}">← Anterior</a>
        @endif

        <span>Página {{ $lista->currentPage() }} de {{ $lista->lastPage() }}</span>

        @if ($lista->hasMorePages())
            <a class="btn btn-suave btn-chico" href="{{ $lista->nextPageUrl() }}">Siguiente →</a>
        @else
            <span class="btn btn-suave btn-chico deshabilitado">Siguiente →</span>
        @endif
    </nav>
@endif
