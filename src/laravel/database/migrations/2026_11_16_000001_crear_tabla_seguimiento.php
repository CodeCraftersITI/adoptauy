<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Seguimiento: entidad débil de Adopcion, uno por adopción (RNE20). Se crea al concretar la
 * adopción con Fecha_Aviso = Fecha_Adopcion + 15 días y Fecha_Limite = Fecha_Aviso + 7 días.
 * Guarda la foto que envía el adoptante y su revisión (RF:0055 a RF:0058).
 * Estado_Evidencia: Pendiente, Enviada, Aprobada, Rechazada.
 * Igual a la tabla de docs/base-de-datos/adoptauy.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Seguimiento', function (Blueprint $table)
        {
            $table->unsignedInteger('ID_Adopcion');
            $table->date('Fecha_Aviso')->comment('Fecha_Adopcion + 15 días');
            $table->date('Fecha_Limite')->comment('Fecha_Aviso + 7 días');
            $table->string('URL_Foto', 255)->nullable();
            $table->dateTime('Fecha_Envio')->nullable();
            $table->enum('Estado_Evidencia', ['Pendiente', 'Enviada', 'Aprobada', 'Rechazada'])->default('Pendiente');

            $table->primary('ID_Adopcion');

            $table->foreign('ID_Adopcion', 'fk_seguimiento_adopcion')
                ->references('ID_Adopcion')->on('Adopcion')
                ->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Seguimiento');
    }
};
