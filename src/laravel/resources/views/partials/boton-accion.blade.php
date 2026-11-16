{{--
    Botón redondo con ícono que envía una acción por PUT (archivar, reactivar, renovar...).
    El nombre aparece al pasar el mouse (title) y lo lee el lector de pantalla (aria-label).
    Uso:
      @include('partials.boton-accion', [
          'url' => '/adopciones/5/archivar',
          'icono' => 'archivar',              // public/img/archivar.png
          'texto' => 'Archivar',
          'clase' => 'btn-fantasma',          // btn-suave, btn-primario o btn-fantasma
          'confirmar' => '¿Archivar?',        // opcional: pide confirmación antes de enviar
      ])
--}}
<form action="{{ $url }}" method="POST"
      @isset($confirmar) onsubmit="return confirm('{{ $confirmar }}')" @endisset>
    @csrf
    @method('PUT')
    <button type="submit" class="btn {{ $clase }} btn-icono-chico" title="{{ $texto }}" aria-label="{{ $texto }}">
        <img class="icono" src="/img/{{ $icono }}.png" alt="">
    </button>
</form>
