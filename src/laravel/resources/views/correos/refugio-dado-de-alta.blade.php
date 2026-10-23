{{-- Correo: alta definitiva del refugio (RF:0005, RF:0063). $refugio llega desde el Mailable. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; color: #3A2430;">
    <h1 style="color: #B03A69;">¡Tu refugio está validado!</h1>

    <p>Hola, {{ $refugio->Nombre_Refugio }}:</p>

    <p>El Administrador revisó tu documentación y tu refugio ya está validado en AdoptaUY.
       Desde ahora podés iniciar sesión con tu correo y tu contraseña.</p>

    <p><a href="{{ url('/ingresar') }}">Ingresar a AdoptaUY</a></p>

    <p>Equipo de AdoptaUY</p>
</body>
</html>
