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
        Schema::create('tipos_clientes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('nombre', 100)->comment('Mayorista, Minorista, Distribuidor, Gobierno, etc');
            $table->text('descripcion')->nullable();
            $table->decimal('descuento_default', 5, 2)->default(0)->comment('Descuento default para este tipo');
            $table->integer('dias_credito_default')->default(0)->comment('Días de crédito default');
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamps();

            // Indexes
            $table->index('activo', 'idx_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_clientes');
    }
};
