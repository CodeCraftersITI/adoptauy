{{--
    Enlaces del menú principal. El enlace de la página actual se marca con nav-enlace-activo.
--}}
<a class="nav-enlace {{ request()->is('/') ? 'nav-enlace-activo' : '' }}" href="/">Inicio</a>
<a class="nav-enlace {{ request()->is('adopcion') ? 'nav-enlace-activo' : '' }}" href="/adopcion">En adopción</a>       {{-- RF:0040, RF:0041 --}}
<a class="nav-enlace {{ request()->is('perdidas') ? 'nav-enlace-activo' : '' }}" href="/perdidas">Perdidas</a>          {{-- RF:0048 --}}
<a class="nav-enlace {{ request()->is('encontradas') ? 'nav-enlace-activo' : '' }}" href="/encontradas">Encontradas</a>  {{-- RF:0065 --}}
<a class="nav-enlace {{ request()->is('refugios*') ? 'nav-enlace-activo' : '' }}" href="/refugios">Refugios</a>        {{-- RF:0038, RF:0042 --}}
<a class="nav-enlace {{ request()->is('nosotros') ? 'nav-enlace-activo' : '' }}" href="/nosotros">Sobre nosotros</a>
