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
        Schema::create('modelos_buses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100)->comment('Ej: Paradiso 1800 DD, Viaggio 1050');
            
            // Índices
            $table->unique('nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelos_buses');
    }
};
