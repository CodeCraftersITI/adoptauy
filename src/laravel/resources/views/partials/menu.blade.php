{{--
    Enlaces del menú principal.
    "En adopción" y "Perdidas" llevan a su sección de la página de inicio.
    "Refugios" (mapa y listado, RF:0038, RF:0042) se agrega en el Hito 3.
--}}
<a class="nav-enlace {{ request()->is('/') ? 'nav-enlace-activo' : '' }}" href="/">Inicio</a>
<a class="nav-enlace" href="/#adopcion">En adopción</a>   {{-- RF:0040 --}}
<a class="nav-enlace" href="/#perdidas">Perdidas</a>      {{-- RF:0048 --}}
<a class="nav-enlace {{ request()->is('nosotros') ? 'nav-enlace-activo' : '' }}" href="/nosotros">Sobre nosotros</a>
