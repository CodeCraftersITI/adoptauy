{{-- Correo: aviso de seguimiento del día 15 (RF:0055, RF:0063). $seguimiento llega desde el Mailable. --}}
@php
    $solicitud = $seguimiento->adopcion->solicitud;
    $mascota = $solicitud->adopcion->publicacion;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; color: #3A2430;">
    <h1 style="color: #B03A69;">¿Cómo está tu mascota?</h1>

    <p>Hola, {{ $solicitud->particular->Nombre }}:</p>

    <p>Ya pasaron 15 días desde que adoptaste a tu {{ strtolower($mascota->Especie) }}
       ({{ $mascota->Raza }}). Para el seguimiento de la adopción, enviá una foto del animal
       antes del {{ date('d/m/Y', strtotime($seguimiento->Fecha_Limite)) }}.</p>

    <p>
        <a href="{{ url('/seguimientos') }}"
           style="display: inline-block; background: #B03A69; color: #FFFFFF; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: bold;">
            Enviar la foto
        </a>
    </p>

    <p>Si no la enviás a tiempo, tu cuenta no va a poder solicitar adopciones ni publicar
       hasta que la envíes.</p>

    <p>Equipo de AdoptaUY</p>
</body>
</html>
