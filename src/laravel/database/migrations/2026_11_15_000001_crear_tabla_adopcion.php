<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adopcion: se crea cuando el publicante elige al adoptante y la solicitud pasa a Adoptada
 * (RF:0052, RNE19). Guarda la fecha de adopción, desde la que se cuentan los 15 y 22 días
 * del seguimiento (RNE20). Igual a la tabla de docs/base-de-datos/adoptauy.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Adopcion', function (Blueprint $table)
        {
            $table->increments('ID_Adopcion');
            $table->unsignedInteger('ID_Solicitud');
            $table->date('Fecha_Adopcion');

            // Una adopción por solicitud
            $table->unique('ID_Solicitud', 'uq_adopcion_solicitud');

            $table->foreign('ID_Solicitud', 'fk_adopcion_solicitud')
                ->references('ID_Solicitud')->on('Solicitud_Adopcion')
                ->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Adopcion');
    }
};
