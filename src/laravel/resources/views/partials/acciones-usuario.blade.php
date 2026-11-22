{{--
    Botones del encabezado según la sesión y el rol (RF:0022, RF:0023).
    session('id_usuario') y session('tipo') los guarda SesionController al iniciar sesión.

    Corrección del docente: botones chicos, solo con ícono (están en public/img).
    El nombre del botón aparece al pasar el mouse (title) y lo lee el lector de pantalla
    (aria-label). En el menú del celular se ve también el texto (texto-movil), porque ahí sobra espacio.
--}}
@if (session('id_usuario'))
    @if (session('tipo') == 'Administrador')
        <a class="btn btn-oscuro btn-icono" href="/admin" title="Panel de administración" aria-label="Panel de administración">
            <img class="icono" src="/img/panel.png" alt="">
            <span class="texto-movil">Panel de administración</span>
        </a>
    @else
        <a class="btn btn-suave btn-icono" href="/cuenta" title="Mi cuenta" aria-label="Mi cuenta">
            <img class="icono" src="/img/mi-cuenta.png" alt="">
            <span class="texto-movil">Mi cuenta</span>
        </a>
        <a class="btn btn-primario btn-icono" href="/publicar" title="Publicar mascota" aria-label="Publicar mascota">
            <img class="icono" src="/img/agregar.png" alt="">
            <span class="texto-movil">Publicar mascota</span>
        </a>
    @endif

    {{-- Cerrar sesión va por POST, con @csrf, igual que cualquier formulario que cambia algo --}}
    <form action="/salir" method="POST">
        @csrf
        <button type="submit" class="btn btn-fantasma btn-icono" title="Cerrar sesión" aria-label="Cerrar sesión">
            <img class="icono" src="/img/cerrar-sesion.png" alt="">
            <span class="texto-movil">Cerrar sesión</span>
        </button>
    </form>
@else
    <a class="btn btn-suave btn-icono" href="/ingresar" title="Ingresar" aria-label="Ingresar">
        <img class="icono" src="/img/ingresar.png" alt="">
        <span class="texto-movil">Ingresar</span>
    </a>
@endif
