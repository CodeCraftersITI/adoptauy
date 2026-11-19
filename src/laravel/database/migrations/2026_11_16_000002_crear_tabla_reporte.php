<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reporte: un usuario reporta una publicación, a un particular o a un refugio (RF:0059);
 * el Administrador lo resuelve (RF:0060) y los umbrales se cuentan sobre esta tabla (RF:0061).
 * Igual a la tabla de docs/base-de-datos/adoptauy.sql, con lo que se agregó en D97:
 *  - Detalle: el texto del motivo "Otro".
 *  - Fecha_Resolucion: cuándo lo resolvió el Administrador ("queda registrada la medida y la fecha").
 *  - UNIQUE: un usuario no reporta dos veces lo mismo (RNE22). Que nadie se reporte a sí
 *    mismo lo valida Laravel.
 * Estado_Reporte: Pendiente, Desestimado, Confirmado. Medida: Aviso, Suspensión, Baja lógica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Reporte', function (Blueprint $table)
        {
            $table->increments('ID_Reporte');
            $table->unsignedInteger('ID_Usuario_Reportante');
            $table->unsignedInteger('ID_Usuario_Reportado')->nullable();
            $table->unsignedInteger('ID_Publicacion_Reportada')->nullable();
            $table->string('Motivo', 120);
            $table->string('Detalle', 500)->nullable()->comment('Texto del motivo Otro');
            $table->dateTime('Fecha_Reporte')->useCurrent();
            $table->enum('Estado_Reporte', ['Pendiente', 'Desestimado', 'Confirmado'])->default('Pendiente');
            $table->enum('Medida', ['Aviso', 'Suspensión', 'Baja lógica'])->nullable()->comment('Se completa al resolver');
            $table->dateTime('Fecha_Resolucion')->nullable();

            // RNE22: un mismo usuario no reporta dos veces al mismo usuario ni a la misma publicación.
            // Las columnas vacías (NULL) no chocan entre sí, así que cada clave controla un tipo de objeto
            $table->unique(['ID_Usuario_Reportante', 'ID_Usuario_Reportado'], 'uq_reporte_usuario');
            $table->unique(['ID_Usuario_Reportante', 'ID_Publicacion_Reportada'], 'uq_reporte_publicacion');

            $table->foreign('ID_Usuario_Reportante', 'fk_reporte_reportante')
                ->references('ID_Usuario')->on('Usuario')
                ->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('ID_Usuario_Reportado', 'fk_reporte_reportado')
                ->references('ID_Usuario')->on('Usuario')
                ->restrictOnUpdate()->restrictOnDelete();
            $table->foreign('ID_Publicacion_Reportada', 'fk_reporte_publicacion')
                ->references('ID_Publicacion')->on('Publicacion_Animal')
                ->restrictOnUpdate()->restrictOnDelete();
        });

        // RNE22: se reporta a un usuario o a una publicación, nunca a los dos ni a ninguno
        DB::statement('ALTER TABLE Reporte ADD CONSTRAINT ck_reporte_objeto CHECK ((ID_Usuario_Reportado IS NULL) <> (ID_Publicacion_Reportada IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('Reporte');
    }
};
