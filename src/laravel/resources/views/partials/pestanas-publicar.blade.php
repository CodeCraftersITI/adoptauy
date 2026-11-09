{{-- Pestañas para elegir qué tipo de publicación crear --}}
<nav class="pestanas" aria-label="Tipo de publicación">
    <a class="pestana {{ request()->is('publicar/adopcion') ? 'pestana-activa' : '' }}" href="/publicar/adopcion">En adopción</a>
    <a class="pestana {{ request()->is('publicar/perdida') ? 'pestana-activa' : '' }}" href="/publicar/perdida">Mascota perdida</a>
</nav>
