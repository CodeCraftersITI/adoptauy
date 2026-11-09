<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Usuario: datos comunes a Particular, Refugio y Administrador (15.2.1).
 * RF:0022, RF:0039. RNE10 (clave candidata ID_Usuario + Tipo_Usuario),
 * RNE13 y RNF:0007 (la contraseña se guarda como hash bcrypt).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Usuario', function (Blueprint $table)
        {
            $table->increments('ID_Usuario');
            $table->string('Email', 255);
            $table->string('Contraseña', 255);
            $table->enum('Tipo_Usuario', ['Particular', 'Refugio', 'Administrador']);
            $table->enum('Estado_Usuario', ['Activo', 'En revisión', 'Suspendido', 'Inhabilitado', 'Baja'])
                ->default('Activo');
            $table->dateTime('Fecha_Alta')->useCurrent();
            $table->dateTime('Email_Verificado')->nullable()
                ->comment('Fecha de confirmación del correo; NULL = sin confirmar (RF:0043)');

            $table->unique('Email', 'uq_usuario_email');
            // Clave candidata que usan los subtipos para cumplir RNE10.
            $table->unique(['ID_Usuario', 'Tipo_Usuario'], 'uq_usuario_id_tipo');
        });

        DB::statement("ALTER TABLE Usuario ADD CONSTRAINT ck_usuario_email CHECK (Email LIKE '_%@_%._%')");
        DB::statement("ALTER TABLE Usuario ADD CONSTRAINT ck_usuario_hash CHECK (`Contraseña` LIKE '$2_$%' AND CHAR_LENGTH(`Contraseña`) = 60)");
    }

    public function down(): void
    {
        Schema::dropIfExists('Usuario');
    }
};
