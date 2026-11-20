<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Para las tablas donde los usuarios de MySQL tienen permiso solo sobre algunas columnas
 * (Usuario, Particular y Refugio; 16.3.4 del integrador, docs/base-de-datos/usuarios.sql).
 *
 * Eloquent pide siempre "SELECT *", y MySQL lo rechaza si el usuario no puede leer todas las
 * columnas. Con este trait, el modelo pide solo las columnas que puede leer el usuario de la
 * conexión que se está usando. Cada modelo lo declara en COLUMNAS_POR_CONEXION; una conexión
 * que no figura (admin, mysql) lee todas.
 *
 * Uso en el modelo:  use ColumnasPermitidas;  y la constante COLUMNAS_POR_CONEXION.
 */
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
