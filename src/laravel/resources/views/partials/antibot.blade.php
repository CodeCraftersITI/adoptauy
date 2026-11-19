{{--
    Campos ocultos contra bots (middleware AntiBot). Se incluye dentro de cada <form> protegido:
      @include('partials.antibot')
    - formulario_inicio: la hora en que se abrió el formulario, cifrada (encrypt) para que no se pueda cambiar.
    - sitio_web: campo trampa. Está fuera de la pantalla, no se llega con Tab y el lector de pantalla
      avisa que se deje vacío. Una persona no lo completa; un bot sí.
--}}
<input type="hidden" name="formulario_inicio" value="{{ encrypt(time()) }}">
<div class="campo-trampa" aria-hidden="true">
    <label>
        No completes este campo
        <input type="text" name="sitio_web" value="" tabindex="-1" autocomplete="off">
    </label>
</div>
