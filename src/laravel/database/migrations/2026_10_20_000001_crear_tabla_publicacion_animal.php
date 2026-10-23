<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Publicacion_Animal: datos comunes a las publicaciones en adopción y de mascotas
 * perdidas (especialización: Publicacion_Adopcion y Publicacion_Perdida).
 * RF:0017, RF:0024, RF:0046. RNE03 (el dueño es ID_Usuario), moderación de 13.2.5.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Publicacion_Animal', function (Blueprint $table)
        {
            $table->increments('ID_Publicacion');
            $table->unsignedInteger('ID_Usuario');
            $table->enum('Tipo_Publicacion', ['Adopción', 'Perdida']);
            $table->enum('Especie', ['Perro', 'Gato']);
            $table->string('Raza', 50);
            $table->enum('Sexo', ['Macho', 'Hembra']);
            $table->enum('Estado_Moderacion', ['Visible', 'En revisión', 'Suspendida'])->default('Visible');
            $table->dateTime('Fecha_Creacion')->useCurrent();

            $table->foreign('ID_Usuario', 'fk_publicacion_usuario')
                ->references('ID_Usuario')->on('Usuario')
                ->restrictOnUpdate()->restrictOnDelete();
            // Clave candidata que usan los subtipos, igual que en Usuario (RNE10)
            $table->unique(['ID_Publicacion', 'Tipo_Publicacion'], 'uq_publicacion_id_tipo');
        });

        DB::statement('ALTER TABLE Publicacion_Animal ADD CONSTRAINT ck_publicacion_raza CHECK (CHAR_LENGTH(TRIM(Raza)) > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('Publicacion_Animal');
    }
};
