<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cuenta_Bancaria: cuentas de un refugio para recibir donaciones por transferencia
 * (RF:0035 a RF:0037, RF:0062). Igual a la tabla de docs/base-de-datos/adoptauy.sql.
 * La clave es (ID_Usuario, ID_Cuenta_Banco): las cuentas se numeran dentro de cada refugio.
 * RNE04: solo un refugio Validado puede darlas de alta (lo controla Laravel).
 * RNE12: Numero_Cuenta es texto, para conservar ceros y guiones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Cuenta_Bancaria', function (Blueprint $table)
        {
            $table->unsignedInteger('ID_Usuario');
            $table->unsignedSmallInteger('ID_Cuenta_Banco');
            $table->string('Banco', 60);
            $table->string('Tipo_Cuenta', 60);
            $table->string('Numero_Cuenta', 30);
            $table->string('Titular', 120);

            $table->primary(['ID_Usuario', 'ID_Cuenta_Banco']);
            $table->foreign('ID_Usuario', 'fk_cuenta_refugio')
                ->references('ID_Usuario')->on('Refugio')
                ->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Cuenta_Bancaria');
    }
};
