<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Refugio_Datos_Contacto: atributo multivaluado de Refugio (15.2.1).
 * Un refugio puede tener varios datos de contacto (teléfono, red social, correo).
 * RF:0034 (datos de contacto verificables).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Refugio_Datos_Contacto', function (Blueprint $table)
        {
            $table->unsignedInteger('ID_Usuario');
            $table->string('Dato_Contacto', 255);

            $table->primary(['ID_Usuario', 'Dato_Contacto']);
            $table->foreign('ID_Usuario', 'fk_refugio_contacto_refugio')
                ->references('ID_Usuario')->on('Refugio')
                ->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Refugio_Datos_Contacto');
    }
};
