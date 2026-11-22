<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\TestCase as BaseTestCase;

// Base de las pruebas funcionales con Laravel Dusk
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
