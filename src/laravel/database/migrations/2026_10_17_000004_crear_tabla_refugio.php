<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refugio: subtipo de Usuario (15.2.1). RF:0022 (inicio de sesión por rol), RF:0034.
 * RNE05: Motivo_Rechazo obligatorio solo en estado Rechazado.
 * RNE10: mismo mecanismo que Particular. RNE24: estados de validación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Refugio', function (Blueprint $table)
        {
            $table->unsignedInteger('ID_Usuario')->primary();
            $table->enum('Tipo_Usuario', ['Particular', 'Refugio', 'Administrador'])->default('Refugio');
            $table->string('Nombre_Refugio', 100);
            $table->string('Certificado_Personeria', 255)->comment('Ruta del archivo subido');
            $table->string('Comprobante_Domicilio', 255)->comment('Ruta del archivo subido');
            $table->string('Ubicacion', 255);
            $table->decimal('Latitud', 9, 6)->nullable();
            $table->decimal('Longitud', 9, 6)->nullable();
            $table->enum('Estado_Validacion', ['Pendiente de validación', 'Validado', 'Rechazado', 'Suspendido', 'Dado de baja'])
                ->default('Pendiente de validación');
            $table->string('Motivo_Rechazo', 500)->nullable();
            $table->dateTime('Fecha_Solicitud')->useCurrent();
            $table->dateTime('Fecha_Resolucion')->nullable();
            $table->string('URL_Foto', 255)->nullable();

            $table->index('Estado_Validacion', 'ix_refugio_estado');
            $table->foreign(['ID_Usuario', 'Tipo_Usuario'], 'fk_refugio_usuario')
                ->references(['ID_Usuario', 'Tipo_Usuario'])->on('Usuario')
                ->restrictOnUpdate()->restrictOnDelete();
        });

        DB::statement("ALTER TABLE Refugio ADD CONSTRAINT ck_refugio_tipo CHECK (Tipo_Usuario = 'Refugio')");
        DB::statement("ALTER TABLE Refugio ADD CONSTRAINT ck_refugio_rne05 CHECK (
            (Estado_Validacion = 'Rechazado' AND Motivo_Rechazo IS NOT NULL AND CHAR_LENGTH(TRIM(Motivo_Rechazo)) > 0)
            OR (Estado_Validacion <> 'Rechazado' AND Motivo_Rechazo IS NULL))");
        DB::statement('ALTER TABLE Refugio ADD CONSTRAINT ck_refugio_resolucion CHECK (Fecha_Resolucion IS NULL OR Fecha_Resolucion >= Fecha_Solicitud)');
        DB::statement('ALTER TABLE Refugio ADD CONSTRAINT ck_refugio_latitud CHECK (Latitud IS NULL OR Latitud BETWEEN -90 AND 90)');
        DB::statement('ALTER TABLE Refugio ADD CONSTRAINT ck_refugio_longitud CHECK (Longitud IS NULL OR Longitud BETWEEN -180 AND 180)');
    }

    public function down(): void
    {
        Schema::dropIfExists('Refugio');
    }
};
