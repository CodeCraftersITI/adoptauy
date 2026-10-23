<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Publicacion_Adopcion: subtipo de Publicacion_Animal para mascotas en adopción.
 * RF:0017, RF:0024. RNE02 (estados En adopción, Adoptado, Archivado),
 * RNE12 (Edad_Meses no negativa, Tamaño de lista cerrada).
 * Repite Tipo_Publicacion con valor fijo y referencia (ID_Publicacion, Tipo_Publicacion):
 * así una publicación de tipo Perdida no puede tener fila acá.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Publicacion_Adopcion', function (Blueprint $table)
        {
            $table->unsignedInteger('ID_Publicacion')->primary();
            $table->enum('Tipo_Publicacion', ['Adopción', 'Perdida'])->default('Adopción');
            $table->unsignedSmallInteger('Edad_Meses');
            $table->enum('Tamaño', ['Pequeño', 'Mediano', 'Grande']);
            $table->string('Estado_Salud', 255);
            $table->enum('Estado_Esterilizacion', ['Esterilizado', 'No esterilizado']);
            $table->string('Ubicacion_Aproximada', 100);
            $table->enum('Estado_Adopcion', ['En adopción', 'Adoptado', 'Archivado'])->default('En adopción');

            $table->foreign(['ID_Publicacion', 'Tipo_Publicacion'], 'fk_adopcion_publicacion')
                ->references(['ID_Publicacion', 'Tipo_Publicacion'])->on('Publicacion_Animal')
                ->restrictOnUpdate()->restrictOnDelete();
            $table->index('Estado_Adopcion', 'ix_adopcion_estado');
        });

        DB::statement("ALTER TABLE Publicacion_Adopcion ADD CONSTRAINT ck_adopcion_tipo CHECK (Tipo_Publicacion = 'Adopción')");
    }

    public function down(): void
    {
        Schema::dropIfExists('Publicacion_Adopcion');
    }
};
