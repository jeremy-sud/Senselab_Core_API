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
        Schema::create('empleados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('nombre', 255);
            $table->string('primer_apellido', 255);
            $table->string('segundo_apellido', 255)->nullable();
            $table->string('tipo_documento', 50);
            $table->string('numero_documento', 100);
            $table->string('email', 255)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->text('direccion')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->unsignedInteger('cargo_id')->nullable();
            $table->decimal('salario', 15, 2)->default(0.00);
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['empresa_id', 'tipo_documento', 'numero_documento'], 'unique_empleado_documento');
            $table->index('empresa_id');
            $table->index('cargo_id');
            $table->index('activo');
            $table->index('eliminado');
            $table->index(['tipo_documento', 'numero_documento']);
            
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('cargo_id')->references('id')->on('cargos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};