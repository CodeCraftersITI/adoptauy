<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto_Animal: fotografías de una publicación (15.2.1).
 * RNE01: entre 1 y 6 fotos en adopción y entre 1 y 3 en perdida, JPG, PNG o WEBP
 * de hasta 8 MB. La cantidad y el formato los controla la aplicación al publicar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Foto_Animal', function (Blueprint $table)
        {
            $table->increments('ID_Foto');
            $table->unsignedInteger('ID_Publicacion');
            $table->string('URL_Foto', 255)->comment('Ruta dentro del disco public');

            $table->foreign('ID_Publicacion', 'fk_foto_publicacion')
                ->references('ID_Publicacion')->on('Publicacion_Animal')
                ->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Foto_Animal');
    }
};
