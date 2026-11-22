<?php

/*
 * Enrutador del servidor de PHP para las pruebas con Dusk (servicio pruebas-web de Docker).
 * Hace lo mismo que Nginx: si se pide un archivo que existe en public/ (CSS, imágenes),
 * lo entrega tal cual; si no, la petición la atiende Laravel (public/index.php).
 */

$publico = __DIR__ . '/../../public';
$ruta = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

if ($ruta !== '/' && is_file($publico . $ruta))
{
    return false;
}

require $publico . '/index.php';
