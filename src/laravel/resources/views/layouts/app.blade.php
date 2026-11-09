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
    <link rel="stylesheet" href="/css/adoptauy.css">
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
</body>
</html>
