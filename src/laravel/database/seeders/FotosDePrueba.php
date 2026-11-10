<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Http;

/**
 * Fotos para los datos de demostración (solo desarrollo, nunca para la demo final ni el repo).
 * Baja una foto al azar de un servicio gratuito: dog.ceo para perros y cataas.com para gatos.
 * Son fotos de terceros: quedan solo en el storage local, que Git no sube.
 * Si no hay internet o el servicio falla, genera una imagen de color con el texto "Foto de prueba".
 * Siempre devuelve el contenido de un JPG.
 */
class FotosDePrueba
{
    public static function jpg($especie)
    {
        $contenido = self::descargar($especie);

        // GD abre la imagen bajada y la vuelve a guardar como JPG: así toda foto queda en
        // el mismo formato y, si lo bajado no era una imagen, imagecreatefromstring da false
        $imagen = $contenido ? @imagecreatefromstring($contenido) : false;

        if (!$imagen)
        {
            $imagen = self::imagenDeColor($especie);
        }

        ob_start();
        imagejpeg($imagen, null, 80);

        return ob_get_clean();
    }

    // Devuelve los bytes de la foto bajada, o null si algo falló
    private static function descargar($especie)
    {
        try
        {
            if ($especie == 'Perro')
            {
                // dog.ceo responde un JSON con la dirección de una foto al azar en "message"
                $respuesta = Http::timeout(10)->get('https://dog.ceo/api/breeds/image/random');
                $url = $respuesta->json('message');
            }
            else
            {
                // cataas.com devuelve directamente una foto de un gato al azar
                $url = 'https://cataas.com/cat?width=800';
            }

            $foto = Http::timeout(15)->get($url);

            return $foto->successful() ? $foto->body() : null;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    // Imagen de reemplazo: un color pastel con el texto "Foto de prueba"
    private static function imagenDeColor($especie)
    {
        $imagen = imagecreatetruecolor(800, 600);
        $fondo = imagecolorallocate($imagen, rand(200, 250), rand(170, 230), rand(190, 240));
        $texto = imagecolorallocate($imagen, 90, 40, 60);
        imagefill($imagen, 0, 0, $fondo);
        imagestring($imagen, 5, 330, 280, 'Foto de prueba', $texto);
        imagestring($imagen, 5, 370, 305, $especie, $texto);

        return $imagen;
    }
}
