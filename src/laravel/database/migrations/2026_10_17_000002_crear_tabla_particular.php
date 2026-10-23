<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Particular: subtipo de Usuario (15.2.1). RF:0039.
 * RNE10: repite Tipo_Usuario con valor fijo y referencia (ID_Usuario, Tipo_Usuario),
 * así un mismo ID no puede estar en dos subtipos.
 * RNE23: CI única de 7 u 8 dígitos; el dígito verificador lo valida la aplicación
 * (App\Rules\CedulaUruguaya).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Particular', function (Blueprint $table)
        {
            $table->unsignedInteger('ID_Usuario')->primary();
            $table->enum('Tipo_Usuario', ['Particular', 'Refugio', 'Administrador'])->default('Particular');
            $table->string('CI', 8);
            $table->string('Nombre', 50);
            $table->string('Apellido', 50);
            $table->string('Telefono', 9);

            $table->unique('CI', 'uq_particular_ci');
            $table->foreign(['ID_Usuario', 'Tipo_Usuario'], 'fk_particular_usuario')
                ->references(['ID_Usuario', 'Tipo_Usuario'])->on('Usuario')
                ->restrictOnUpdate()->restrictOnDelete();
        });

        DB::statement("ALTER TABLE Particular ADD CONSTRAINT ck_particular_tipo CHECK (Tipo_Usuario = 'Particular')");
        DB::statement("ALTER TABLE Particular ADD CONSTRAINT ck_particular_ci CHECK (REGEXP_LIKE(CI, '^[0-9]{7,8}$'))");
        DB::statement('ALTER TABLE Particular ADD CONSTRAINT ck_particular_nombre CHECK (CHAR_LENGTH(Nombre) BETWEEN 2 AND 50)');
        DB::statement('ALTER TABLE Particular ADD CONSTRAINT ck_particular_apellido CHECK (CHAR_LENGTH(Apellido) BETWEEN 2 AND 50)');
        DB::statement("ALTER TABLE Particular ADD CONSTRAINT ck_particular_telefono CHECK (REGEXP_LIKE(Telefono, '^[0-9]{8,9}$'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('Particular');
    }
};
