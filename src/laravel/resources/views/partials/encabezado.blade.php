{{-- Encabezado con el logo, el menú y los botones de la sesión (se incluye desde layouts.app) --}}
<header class="encabezado">
    <div class="contenedor encabezado-barra">
        <a class="marca" href="/">
            <span class="marca-icono">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="5.5" cy="10" r="2"/><circle cx="9.5" cy="5.5" r="2"/><circle cx="14.5" cy="5.5" r="2"/><circle cx="18.5" cy="10" r="2"/><path d="M12 11c-3 0-6 4.5-6 7a2.5 2.5 0 0 0 3.5 2.3c1.6-.6 3.4-.6 5 0A2.5 2.5 0 0 0 18 18c0-2.5-3-7-6-7z"/></svg>
            </span>
            AdoptaUY
        </a>

        {{-- Computadora: menú y botones en la barra --}}
        <nav class="nav-principal">
            @include('partials.menu')
        </nav>
        <div class="nav-acciones">
            @include('partials.acciones-usuario')
        </div>

        {{-- Celular: los mismos partials dentro de un menú desplegable (<details> no necesita JavaScript) --}}
        <details class="menu-movil">
            <summary class="btn btn-suave" aria-label="Abrir menú">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </summary>
            <nav class="menu-movil-panel">
                @include('partials.menu')
                @include('partials.acciones-usuario')
            </nav>
        </details>
    </div>
</header>
