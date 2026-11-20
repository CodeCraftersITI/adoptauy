<?php

namespace Tests;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // El inicio de sesión y el registro usan las conexiones mysql_login y mysql_publico, que
    // entran a MySQL con otro usuario (config/database.php, 16.3.4). En las pruebas comunes
    // esas conexiones son la misma conexión de la aplicación: así quedan dentro de la
    // transacción de RefreshDatabase, ven los datos que arma la prueba y se deshacen al
    // terminar. Los privilegios reales se prueban en ConexionesPorRolTest, que pone esto en true.
    protected bool $conexionesReales = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->conexionesReales)
        {
            $db = $this->app['db'];

            foreach ([Usuario::CONEXION_LOGIN, Usuario::CONEXION_REGISTRO] as $conexion)
            {
                $db->purge($conexion);
                $db->extend($conexion, fn () => $db->connection());
            }
        }
    }
}
