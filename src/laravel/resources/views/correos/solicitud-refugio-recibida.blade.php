{{-- Correo: solicitud de registro de refugio recibida (RF:0034, RF:0063). $refugio llega desde el Mailable. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; color: #3A2430;">
    <h1 style="color: #B03A69;">Recibimos tu solicitud</h1>

    <p>Hola, {{ $refugio->Nombre_Refugio }}:</p>

    <p>Recibimos tu solicitud de registro en AdoptaUY. El Administrador va a revisar tu documentación
       en un plazo máximo de 5 días hábiles y te vamos a avisar por correo.</p>

    <p>Mientras tanto, la cuenta todavía no permite iniciar sesión.</p>

    <p>Equipo de AdoptaUY</p>
</body>
</html>
