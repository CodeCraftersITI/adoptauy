<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Mascotas encontradas en la misma tabla que las perdidas (RF:0064, RF:0065; modifica RF:0046,
 * RF:0047 y RF:0048). RNE02 (estados nuevos), RNE16 (la publican Particular o Refugio),
 * RNE17 (misma vigencia de 30 días) y RNE26 (nombre obligatorio solo en las perdidas).
 * Una mascota encontrada usa los mismos datos que una perdida, así que no tiene tabla propia:
 * Tipo_Aviso indica si quien publica perdió a su mascota (Perdida) o encontró una ajena (Encontrada).
 *
 * - Tipo_Aviso: 'Perdida' o 'Encontrada'.
 * - Fecha_Extravio y Zona_Extravio pasan a Fecha_Suceso y Zona_Suceso: en una perdida son la
 *   fecha y la zona del extravío; en una encontrada, las del hallazgo.
 * - Nombre_Mascota pasa a ser opcional, pero solo para las encontradas (ck_perdida_nombre).
 * - Estado_Perdida pasa de Perdida, Encontrada y Archivada a Vigente, Resuelta y Archivada,
 *   para no confundir el estado «Encontrada» con el tipo nuevo (Resuelta = la mascota volvió con su dueño).
 */
return new class extends Migration
{
    public function up(): void
    {
        // El CHECK usa Fecha_Extravio: se quita antes de renombrar la columna y se vuelve a crear
        DB::statement('ALTER TABLE Publicacion_Perdida DROP CHECK ck_perdida_vencimiento');

        DB::statement("ALTER TABLE Publicacion_Perdida
            ADD COLUMN Tipo_Aviso ENUM('Perdida','Encontrada') NOT NULL DEFAULT 'Perdida' AFTER Tipo_Publicacion,
            RENAME COLUMN Fecha_Extravio TO Fecha_Suceso,
            RENAME COLUMN Zona_Extravio TO Zona_Suceso,
            MODIFY Nombre_Mascota VARCHAR(50) NULL");

        // Estados: primero se admiten los viejos y los nuevos, se traducen y después quedan solo los nuevos
        DB::statement("ALTER TABLE Publicacion_Perdida MODIFY Estado_Perdida
            ENUM('Perdida','Encontrada','Archivada','Vigente','Resuelta') NOT NULL DEFAULT 'Vigente'");
        DB::table('Publicacion_Perdida')->where('Estado_Perdida', 'Perdida')->update(['Estado_Perdida' => 'Vigente']);
        DB::table('Publicacion_Perdida')->where('Estado_Perdida', 'Encontrada')->update(['Estado_Perdida' => 'Resuelta']);
        DB::statement("ALTER TABLE Publicacion_Perdida MODIFY Estado_Perdida
            ENUM('Vigente','Resuelta','Archivada') NOT NULL DEFAULT 'Vigente'");

        DB::statement('ALTER TABLE Publicacion_Perdida ADD CONSTRAINT ck_perdida_vencimiento CHECK (Fecha_Vencimiento > Fecha_Suceso)');
        DB::statement("ALTER TABLE Publicacion_Perdida ADD CONSTRAINT ck_perdida_nombre CHECK (Tipo_Aviso = 'Encontrada' OR Nombre_Mascota IS NOT NULL)");

        // Cada apartado de la consulta pública filtra por tipo, estado y vencimiento
        DB::statement('ALTER TABLE Publicacion_Perdida DROP INDEX ix_perdida_estado_vencimiento,
            ADD INDEX ix_perdida_tipo_estado_vencimiento (Tipo_Aviso, Estado_Perdida, Fecha_Vencimiento)');
    }

    public function down(): void
    {
        // Las encontradas no existían antes: se archivan para no perderlas, con un nombre provisorio
        DB::table('Publicacion_Perdida')->whereNull('Nombre_Mascota')->update(['Nombre_Mascota' => 'Sin nombre']);

        DB::statement('ALTER TABLE Publicacion_Perdida DROP CHECK ck_perdida_nombre');
        DB::statement('ALTER TABLE Publicacion_Perdida DROP CHECK ck_perdida_vencimiento');
        DB::statement('ALTER TABLE Publicacion_Perdida DROP INDEX ix_perdida_tipo_estado_vencimiento,
            ADD INDEX ix_perdida_estado_vencimiento (Estado_Perdida, Fecha_Vencimiento)');

        DB::statement("ALTER TABLE Publicacion_Perdida MODIFY Estado_Perdida
            ENUM('Perdida','Encontrada','Archivada','Vigente','Resuelta') NOT NULL DEFAULT 'Perdida'");
        DB::table('Publicacion_Perdida')->where('Tipo_Aviso', 'Encontrada')->update(['Estado_Perdida' => 'Archivada']);
        DB::table('Publicacion_Perdida')->where('Estado_Perdida', 'Vigente')->update(['Estado_Perdida' => 'Perdida']);
        DB::table('Publicacion_Perdida')->where('Estado_Perdida', 'Resuelta')->update(['Estado_Perdida' => 'Encontrada']);
        DB::statement("ALTER TABLE Publicacion_Perdida MODIFY Estado_Perdida
            ENUM('Perdida','Encontrada','Archivada') NOT NULL DEFAULT 'Perdida'");

        DB::statement("ALTER TABLE Publicacion_Perdida
            DROP COLUMN Tipo_Aviso,
            RENAME COLUMN Fecha_Suceso TO Fecha_Extravio,
            RENAME COLUMN Zona_Suceso TO Zona_Extravio,
            MODIFY Nombre_Mascota VARCHAR(50) NOT NULL");
        DB::statement('ALTER TABLE Publicacion_Perdida ADD CONSTRAINT ck_perdida_vencimiento CHECK (Fecha_Vencimiento > Fecha_Extravio)');
    }
};
