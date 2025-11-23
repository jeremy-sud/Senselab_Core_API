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
        Schema::create('logs_acceso_sistema', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('usuario_id')->nullable();
            $table->string('email', 191)->nullable()->comment('Email usado en intento de login');
            $table->enum('tipo_evento', ['login_exitoso', 'login_fallido', 'logout', 'cambio_password', 'reset_password', 'bloqueo_cuenta', 'desbloqueo_cuenta']);
            $table->string('ip_address', 45)->comment('IPv4 o IPv6');
            $table->string('user_agent', 255)->nullable();
            $table->string('metodo_autenticacion', 50)->nullable()->comment('password, 2fa, token, etc');
            $table->string('razon_fallo', 255)->nullable()->comment('Si login_fallido');
            $table->string('sesion_id', 191)->nullable();
            $table->integer('duracion_sesion')->nullable()->comment('Segundos de duración de sesión');
            $table->string('pais', 2)->nullable()->comment('Código ISO país');
            $table->string('ciudad', 100)->nullable();
            $table->timestamp('creado_en')->useCurrent();

            // Foreign keys
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('set null');

            // Indexes
            $table->index(['usuario_id', 'creado_en'], 'idx_usuario_fecha');
            $table->index('tipo_evento', 'idx_tipo_evento');
            $table->index('ip_address', 'idx_ip');
            $table->index('creado_en', 'idx_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_acceso_sistema');
    }
};
