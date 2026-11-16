{{--
    Layout principal de AdoptaUY.
    Cada página lo usa con @extends('layouts.app') y completa dos secciones:
      - titulo:    el texto de la pestaña del navegador
      - contenido: el cuerpo de la página
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') · AdoptaUY</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;700;800&display=swap">
    {{-- ?v=fecha de modificación: cuando el CSS cambia, cambia la dirección y el navegador
         baja el archivo nuevo en lugar de usar la copia vieja que tenía guardada (caché) --}}
    <link rel="stylesheet" href="/css/adoptauy.css?v={{ filemtime(public_path('css/adoptauy.css')) }}">

    {{-- Lo que una página necesite agregar en el head (por ejemplo, el mapa de Leaflet) --}}
    @yield('cabecera')
</head>
<body>
    @include('partials.encabezado')

    <main class="principal">
        <div class="contenedor">
            @include('partials.mensajes')

            @yield('contenido')
        </div>
    </main>

    @include('partials.pie')

    <script>
        // Campos que solo aceptan números (cédula, teléfono): mientras se escribe,
        // se borra todo lo que no sea un dígito del 0 al 9
        document.querySelectorAll('.solo-numeros').forEach(function (campo)
        {
            campo.addEventListener('input', function ()
            {
                campo.value = campo.value.replace(/[^0-9]/g, '');
            });
        });
    </script>
</body>
</html>
