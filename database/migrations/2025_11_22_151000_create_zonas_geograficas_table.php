<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zonas_geograficas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('empresa_id')->nullable()->comment('NULL si es catálogo nacional');
            $table->string('codigo', 10);
            $table->string('nombre', 100)->comment('San José Centro, Alajuela Norte, etc');
            $table->enum('tipo', ['provincia', 'canton', 'distrito', 'zona_ventas', 'ruta']);
            $table->unsignedBigInteger('zona_padre_id')->nullable()->comment('Para jerarquía de zonas');
            $table->json('provincias_incluidas')->nullable()->comment('Array de provincias si es zona_ventas');
            $table->unsignedInteger('vendedor_asignado_id')->nullable();
            $table->boolean('activa')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Foreign keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('zona_padre_id')->references('id')->on('zonas_geograficas')->onDelete('set null');
            $table->foreign('vendedor_asignado_id')->references('id')->on('empleados')->onDelete('set null');

            // Indexes
            $table->index('tipo', 'idx_zonas_geo_tipo');
            $table->index('empresa_id', 'idx_zonas_geo_empresa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zonas_geograficas');
    }
};
