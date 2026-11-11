<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitud_Adopcion: un Particular pide adoptar una mascota En adopción (RF:0049).
 * Igual a la tabla de docs/base-de-datos/adoptauy.sql. RNE18 (solo sobre una publicación
 * En adopción y no propia) y la regla de una sola Pendiente por publicación las valida Laravel.
 * Estados: Pendiente, Adoptada, Rechazada (13.2.3).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Solicitud_Adopcion', function (Blueprint $table)
        {
            $table->increments('ID_Solicitud');
            $table->unsignedInteger('ID_Publicacion');
            $table->unsignedInteger('ID_Usuario')->comment('Particular que solicita');
            $table->dateTime('Fecha_Solicitud')->useCurrent();
            $table->enum('Estado_Solicitud', ['Pendiente', 'Adoptada', 'Rechazada'])->default('Pendiente');

            $table->foreign('ID_Publicacion', 'fk_solicitud_publicacion')
                ->references('ID_Publicacion')->on('Publicacion_Adopcion')
                ->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('ID_Usuario', 'fk_solicitud_particular')
                ->references('ID_Usuario')->on('Particular')
                ->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Solicitud_Adopcion');
    }
};
