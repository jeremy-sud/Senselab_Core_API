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
        Schema::table('ventas', function (Blueprint $table) {
            $table->decimal('monto_total_venta', 10, 2)->default(0.00)->change();
            $table->string('estado_venta', 50)->default('Pendiente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // No podemos revertir fácilmente el cambio de default sin saber el estado anterior
            // En producción, considerar si es necesario revertir
        });
    }
};
