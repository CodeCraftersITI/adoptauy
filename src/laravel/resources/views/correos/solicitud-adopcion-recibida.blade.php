{{-- Correo: solicitud de adopción recibida (RF:0049, RF:0063). $solicitud llega desde el Mailable. --}}
@php
    $publicacion = $solicitud->adopcion->publicacion;
    $solicitante = $solicitud->particular;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; color: #3A2430;">
    <h1 style="color: #B03A69;">Recibiste una solicitud de adopción</h1>

    <p>Alguien quiere adoptar a tu {{ strtolower($publicacion->Especie) }} {{ $publicacion->Raza }}
       ({{ $solicitud->adopcion->Ubicacion_Aproximada }}).</p>

    <p>Aceptó compartir estos datos para que te comuniques:</p>
    <ul>
        <li><strong>Nombre:</strong> {{ $solicitante->Nombre }} {{ $solicitante->Apellido }}</li>
        <li><strong>Teléfono:</strong> {{ $solicitante->Telefono }}</li>
        <li><strong>Correo:</strong> {{ $solicitante->usuario->Email }}</li>
    </ul>

    <p><a href="{{ url('/publicaciones/' . $publicacion->ID_Publicacion . '/solicitudes') }}">Ver todas las solicitudes de esta publicación</a></p>

    <p>Equipo de AdoptaUY</p>
</body>
</html>
