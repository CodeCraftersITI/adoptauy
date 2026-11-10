<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Continuar con Google (RF:0066, D93, RNE27).
 * Usuario suma Google_ID (el «sub» que envía Google, único y opcional) y Contraseña
 * pasa a admitir NULL: una cuenta creada con Google no tiene contraseña hasta que el
 * Particular define una (RF:0067). Sigue lo definido en modelo-datos.md
 * («Cambio pendiente de migración») y docs/base-de-datos/adoptauy.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        // El CHECK del hash se vuelve a crear para que acepte NULL (RNE13, RNE27)
        DB::statement('ALTER TABLE Usuario DROP CHECK ck_usuario_hash');

        Schema::table('Usuario', function (Blueprint $table)
        {
            $table->string('Contraseña', 255)->nullable()->change();
            $table->string('Google_ID', 255)->nullable()->after('Email_Verificado');

            $table->unique('Google_ID', 'uq_usuario_google');
        });

        DB::statement("ALTER TABLE Usuario ADD CONSTRAINT ck_usuario_hash CHECK (`Contraseña` IS NULL OR (`Contraseña` LIKE '$2_$%' AND CHAR_LENGTH(`Contraseña`) = 60))");
        // RNE27: sin contraseña solo si tiene Google_ID, y Google_ID solo en un Particular
        DB::statement('ALTER TABLE Usuario ADD CONSTRAINT ck_usuario_acceso CHECK (`Contraseña` IS NOT NULL OR Google_ID IS NOT NULL)');
        DB::statement("ALTER TABLE Usuario ADD CONSTRAINT ck_usuario_google CHECK (Google_ID IS NULL OR Tipo_Usuario = 'Particular')");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE Usuario DROP CHECK ck_usuario_google');
        DB::statement('ALTER TABLE Usuario DROP CHECK ck_usuario_acceso');
        DB::statement('ALTER TABLE Usuario DROP CHECK ck_usuario_hash');

        Schema::table('Usuario', function (Blueprint $table)
        {
            $table->dropUnique('uq_usuario_google');
            $table->dropColumn('Google_ID');
            $table->string('Contraseña', 255)->nullable(false)->change();
        });

        DB::statement("ALTER TABLE Usuario ADD CONSTRAINT ck_usuario_hash CHECK (`Contraseña` LIKE '$2_$%' AND CHAR_LENGTH(`Contraseña`) = 60)");
    }
};
