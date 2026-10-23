{{--
    Botones del encabezado según la sesión y el rol (RF:0022, RF:0023).
    session('id_usuario') y session('tipo') los guarda SesionController al iniciar sesión.
--}}
@if (session('id_usuario'))
    @if (session('tipo') == 'Administrador')
        <a class="btn btn-oscuro" href="/admin">Panel de administración</a>
    @else
        <a class="btn btn-suave" href="/cuenta">Mi cuenta</a>
        <a class="btn btn-primario" href="/publicar/adopcion">Publicar</a>
    @endif

    {{-- Cerrar sesión va por POST, con @csrf, igual que cualquier formulario que cambia algo --}}
    <form action="/salir" method="POST">
        @csrf
        <button type="submit" class="btn btn-fantasma">Cerrar sesión</button>
    </form>
@else
    <a class="btn btn-suave" href="/ingresar">Ingresar</a>
@endif
