{{-- Correo: confirmar la dirección del Particular (RF:0043, RF:0063). $usuario y $enlace llegan desde el Mailable. --}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: sans-serif; color: #3A2430;">
    <h1 style="color: #B03A69;">Confirmá tu correo</h1>

    {{-- Sin el nombre: lo envían adoptauy_publico y adoptauy_login, que no lo pueden leer (16.3.4) --}}
    <p>Hola:</p>

    <p>Gracias por crear tu cuenta en AdoptaUY. Para poder ingresar, confirmá que este correo es tuyo:</p>

    <p>
        <a href="{{ $enlace }}"
           style="display: inline-block; background: #B03A69; color: #FFFFFF; padding: 12px 20px; border-radius: 12px; text-decoration: none; font-weight: bold;">
            Confirmar mi correo
        </a>
    </p>

    <p>Si el botón no funciona, copiá este enlace en el navegador:<br>{{ $enlace }}</p>

    <p>Si no creaste una cuenta en AdoptaUY, ignorá este correo.</p>

    <p>Equipo de AdoptaUY</p>
</body>
</html>
