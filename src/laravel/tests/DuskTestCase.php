<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\TestCase as BaseTestCase;

/**
 * Base de las pruebas funcionales con Laravel Dusk (S-03, plan de pruebas): un Chrome real
 * recorre las pantallas como lo haría una persona.
 *
 * En Docker (perfil "pruebas", ver docker-compose.yml):
 *  - selenium:    Chrome sin pantalla, que maneja Dusk a distancia.
 *  - pruebas-web: el sitio servido con "php artisan serve" contra la base adoptauy_test.
 * Se corre con:
 *   docker compose --profile pruebas up -d
 *   docker compose exec app php artisan dusk
 *
 * IMPORTANTE: las pruebas borran y recrean la base. Por eso, antes de empezar se comprueba
 * que la conexión sea adoptauy_test; si no, la prueba se corta y no toca nada.
 */
abstract class DuskTestCase extends BaseTestCase
{
    // Dirección del sitio de pruebas, vista desde el contenedor de Chrome
    protected function baseUrl()
    {
        return env('DUSK_URL', 'http://pruebas-web:8000');
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Seguro contra borrar la base de desarrollo
        if (DB::connection()->getDatabaseName() != 'adoptauy_test')
        {
            $this->fail('Dusk solo corre contra adoptauy_test, y la conexión es '
                . DB::connection()->getDatabaseName() . '. No se tocó la base.');
        }
    }

    // Chrome del contenedor selenium (no se arranca un ChromeDriver local)
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments([
            '--window-size=1280,900',
            '--headless=new',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ]);

        return RemoteWebDriver::create(
            env('DUSK_DRIVER_URL', 'http://selenium:4444/wd/hub'),
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options)
        );
    }
}
