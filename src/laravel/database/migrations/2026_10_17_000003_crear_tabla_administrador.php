<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Administrador: subtipo de Usuario (15.2.1). RF:0022 (inicio de sesión por rol).
 * RNE10: mismo mecanismo que Particular.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Administrador', function (Blueprint $table)
        {
            $table->unsignedInteger('ID_Usuario')->primary();
            $table->enum('Tipo_Usuario', ['Particular', 'Refugio', 'Administrador'])->default('Administrador');

            $table->foreign(['ID_Usuario', 'Tipo_Usuario'], 'fk_administrador_usuario')
                ->references(['ID_Usuario', 'Tipo_Usuario'])->on('Usuario')
                ->restrictOnUpdate()->restrictOnDelete();
        });

        DB::statement("ALTER TABLE Administrador ADD CONSTRAINT ck_administrador_tipo CHECK (Tipo_Usuario = 'Administrador')");
    }

    public function down(): void
    {
        Schema::dropIfExists('Administrador');
    }
};
