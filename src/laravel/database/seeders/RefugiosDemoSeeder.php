<?php

namespace Database\Seeders;

use App\Models\CuentaBancaria;
use App\Models\Refugio;
use App\Models\RefugioDatoContacto;
use App\Models\Usuario;

/**
 * Datos de demostración de la sección Refugios (RF:0035 a RF:0038, RF:0042, RF:0062):
 * refugios ficticios con su ubicación en el mapa, contacto, cuentas para donaciones y mascotas.
 * Todo es inventado para probar (Ley 18.331): no son refugios reales y las cuentas no existen.
 * Las coordenadas son las de cada barrio, no de un lugar real.
 *
 * Usa los métodos de DatosDemoSeeder para crear las publicaciones con sus fotos.
 * Se carga a mano:
 *   docker compose exec app php artisan db:seed --class=RefugiosDemoSeeder
 * Se puede correr más de una vez: si el refugio ya existe (por su correo), lo saltea.
 * Para entrar como uno de ellos: su correo y la contraseña adoptauy-prueba.
 */
class RefugiosDemoSeeder extends DatosDemoSeeder
{
    // Barrio, latitud, longitud, cuántas cuentas y estado. El Pendiente no tiene que verse en ningún lado.
    private const REFUGIOS = [
        ['Pocitos', -34.9105, -56.1500, 2, 'Validado'],
        ['Malvín', -34.8930, -56.1080, 1, 'Validado'],
        ['Prado', -34.8580, -56.2030, 2, 'Validado'],
        ['Cerro', -34.8870, -56.2600, 1, 'Validado'],
        ['Carrasco', -34.8840, -56.0570, 0, 'Validado'],   // sin cuentas: el perfil lo avisa
        ['Unión', -34.8700, -56.1430, 1, 'Validado'],
        ['Sayago', -34.8350, -56.2150, 0, 'Pendiente de validación'],
    ];

    private const BANCOS = ['BROU', 'Santander', 'Itaú', 'BBVA', 'Scotiabank', 'HSBC'];

    private const TIPOS_CUENTA = ['Caja de ahorro en pesos', 'Caja de ahorro en dólares', 'Cuenta corriente en pesos'];

    public function run(): void
    {
        $creados = 0;

        foreach (self::REFUGIOS as $numero => $datos)
        {
            [$barrio, $latitud, $longitud, $cuentas, $estado] = $datos;
            $email = 'refugio' . ($numero + 1) . '@prueba.uy';

            if (Usuario::where('Email', $email)->exists())
            {
                continue;
            }

            $usuario = Usuario::factory()->create([
                'Email' => $email,
                'Tipo_Usuario' => 'Refugio',
                'Fecha_Alta' => now()->subDays(60),
                'Email_Verificado' => now()->subDays(60),
            ]);

            // El Pendiente todavía no tiene fecha de resolución
            $fabrica = $estado == 'Validado' ? Refugio::factory()->validado() : Refugio::factory();

            $refugio = $fabrica->create([
                'ID_Usuario' => $usuario->ID_Usuario,
                'Nombre_Refugio' => 'Refugio de prueba ' . $barrio,
                'Ubicacion' => $barrio . ', Montevideo',
                'Latitud' => $latitud,
                'Longitud' => $longitud,
                'Estado_Validacion' => $estado,
            ]);

            RefugioDatoContacto::create([
                'ID_Usuario' => $refugio->ID_Usuario,
                'Dato_Contacto' => '09' . fake()->numerify('#######'),
            ]);

            $this->crearCuentas($refugio, $cuentas);
            $creados++;

            // Un refugio Pendiente no puede iniciar sesión, así que no publica mascotas
            if ($estado != 'Validado')
            {
                continue;
            }

            // Entre 2 y 5 mascotas en adopción, y a veces una perdida o una encontrada
            $cantidad = rand(2, 5);
            for ($i = 0; $i < $cantidad; $i++)
            {
                $this->crearAdopcion($usuario);
            }

            if (rand(0, 1))
            {
                $this->crearAviso($usuario, fake()->randomElement(['Perdida', 'Encontrada']));
            }
        }

        $this->command->info('Refugios de demostración cargados: ' . $creados
            . '. Correos refugio1@prueba.uy a refugio' . count(self::REFUGIOS) . '@prueba.uy, contraseña adoptauy-prueba.');
    }

    // Cuentas ficticias: el número empieza con 000 para que se note que es de prueba
    private function crearCuentas($refugio, $cantidad)
    {
        for ($i = 1; $i <= $cantidad; $i++)
        {
            $cuenta = new CuentaBancaria([
                'Banco' => fake()->randomElement(self::BANCOS),
                'Tipo_Cuenta' => fake()->randomElement(self::TIPOS_CUENTA),
                'Numero_Cuenta' => '000' . fake()->numerify('#######'),
                'Titular' => $refugio->Nombre_Refugio,
            ]);
            $cuenta->ID_Usuario = $refugio->ID_Usuario;
            $cuenta->ID_Cuenta_Banco = $i;
            $cuenta->save();
        }
    }
}
