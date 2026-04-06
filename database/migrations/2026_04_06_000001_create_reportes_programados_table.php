<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_programados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('nombre', 100);
            $table->string('tipo_reporte', 50);
            $table->string('frecuencia', 20); // diario, semanal, mensual
            $table->string('formato', 10)->default('pdf');
            $table->json('filtros')->nullable();
            $table->json('destinatarios'); // array de emails
            $table->string('dia_semana', 10)->nullable(); // para semanal
            $table->unsignedTinyInteger('dia_mes')->nullable(); // para mensual
            $table->time('hora_envio')->default('07:00');
            $table->timestamp('ultima_ejecucion')->nullable();
            $table->timestamp('proxima_ejecucion')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('eliminado')->default(false);
            $table->timestamp('creado_en')->nullable();
            $table->timestamp('actualizado_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_programados');
    }
};
