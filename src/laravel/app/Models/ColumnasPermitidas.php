<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

// Pide solo las columnas que el usuario de MySQL puede leer (Usuario, Particular y Refugio)
trait ColumnasPermitidas
{
    // Laravel llama a este método solo al usar el trait (boot + nombre del trait)
    protected static function bootColumnasPermitidas()
    {
        static::addGlobalScope('columnas_permitidas', function (Builder $consulta)
        {
            // Si la consulta ya eligió sus columnas, no se toca
            if ($consulta->getQuery()->columns !== null)
            {
                return;
            }

            $conexion = $consulta->getQuery()->getConnection()->getName();
            $columnas = static::COLUMNAS_POR_CONEXION[$conexion] ?? null;

            if ($columnas)
            {
                $modelo = $consulta->getModel();
                $consulta->select(array_map(fn ($columna) => $modelo->qualifyColumn($columna), $columnas));
            }
        });
    }
}
