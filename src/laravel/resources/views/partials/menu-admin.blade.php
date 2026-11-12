{{-- Pestañas del panel de administración. El enlace de la página actual se marca con btn-primario. --}}
<nav class="nav-acciones-fila" aria-label="Secciones del panel">
    <a class="btn {{ request()->is('admin') ? 'btn-primario' : 'btn-suave' }}" href="/admin">Solicitudes de refugio</a>
    <a class="btn {{ request()->is('admin/publicaciones*') ? 'btn-primario' : 'btn-suave' }}" href="/admin/publicaciones">Publicaciones</a>
    <a class="btn {{ request()->is('admin/refugios*') ? 'btn-primario' : 'btn-suave' }}" href="/admin/refugios">Refugios</a>
    <a class="btn {{ request()->is('admin/particulares*') ? 'btn-primario' : 'btn-suave' }}" href="/admin/particulares">Particulares</a>
</nav>
