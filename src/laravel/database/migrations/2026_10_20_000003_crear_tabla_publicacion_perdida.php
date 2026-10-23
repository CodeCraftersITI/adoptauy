<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Publicacion_Perdida: subtipo de Publicacion_Animal para mascotas perdidas.
 * RF:0046, RF:0047, RF:0048. RNE02 (estados Perdida, Encontrada, Archivada),
 * RNE16, RNE17 (Fecha_Vencimiento = creación o última renovación + 30 días).
 * Mismo mecanismo de clave que Publicacion_Adopcion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Publicacion_Perdida', function (Blueprint $table)
        {
            $table->unsignedInteger('ID_Publicacion')->primary();
            $table->enum('Tipo_Publicacion', ['Adopción', 'Perdida'])->default('Perdida');
            $table->string('Nombre_Mascota', 50);
            $table->text('Caracteristicas');
            $table->date('Fecha_Extravio');
            $table->string('Zona_Extravio', 100);
            $table->string('Contacto', 100)->comment('Público: lo ve cualquier visitante (13.2.2)');
            $table->date('Fecha_Vencimiento');
            $table->enum('Estado_Perdida', ['Perdida', 'Encontrada', 'Archivada'])->default('Perdida');

            $table->foreign(['ID_Publicacion', 'Tipo_Publicacion'], 'fk_perdida_publicacion')
                ->references(['ID_Publicacion', 'Tipo_Publicacion'])->on('Publicacion_Animal')
                ->restrictOnUpdate()->restrictOnDelete();
            $table->index(['Estado_Perdida', 'Fecha_Vencimiento'], 'ix_perdida_estado_vencimiento');
        });

        DB::statement("ALTER TABLE Publicacion_Perdida ADD CONSTRAINT ck_perdida_tipo CHECK (Tipo_Publicacion = 'Perdida')");
        DB::statement('ALTER TABLE Publicacion_Perdida ADD CONSTRAINT ck_perdida_vencimiento CHECK (Fecha_Vencimiento > Fecha_Extravio)');
    }

    public function down(): void
    {
        Schema::dropIfExists('Publicacion_Perdida');
    }
};
