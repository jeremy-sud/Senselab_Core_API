<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade columna `es_principal` a la tabla `sucursales`.
 * SucursalService ya la utiliza para gestionar sucursal principal por empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->boolean('es_principal')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {
            $table->dropColumn('es_principal');
        });
    }
};
