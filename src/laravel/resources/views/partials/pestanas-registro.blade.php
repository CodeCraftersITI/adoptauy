{{-- Pestañas para elegir el tipo de cuenta en el registro (como en el prototipo) --}}
<nav class="pestanas" aria-label="Tipo de cuenta">
    <a class="pestana {{ request()->is('registro') ? 'pestana-activa' : '' }}" href="/registro">Particular</a>
    <a class="pestana {{ request()->is('registro-refugio') ? 'pestana-activa' : '' }}" href="/registro-refugio">Refugio</a>
</nav>
