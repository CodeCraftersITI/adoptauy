{{-- Enlaces del menú principal. Los que tienen href="#" son páginas que todavía no existen. --}}
<a class="nav-enlace {{ request()->is('/') ? 'nav-enlace-activo' : '' }}" href="/">Inicio</a>
<a class="nav-enlace" href="#">En adopción</a>      {{-- RF:0040 --}}
<a class="nav-enlace" href="#">Perdidas</a>         {{-- RF:0048 --}}
<a class="nav-enlace" href="#">Refugios</a>         {{-- RF:0042 --}}
<a class="nav-enlace" href="#">Sobre nosotros</a>
