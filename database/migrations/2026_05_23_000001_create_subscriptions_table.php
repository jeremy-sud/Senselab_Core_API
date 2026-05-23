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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tenant_id')->unique(); // e.g. sl_tenant_000001 or sl_usr_8f6d2e9c13b5
            $table->unsignedInteger('empresa_id')->nullable();
            $table->unsignedInteger('usuario_id')->nullable();
            $table->enum('plan', ['free', 'starter', 'pro', 'business', 'enterprise'])->default('starter');
            $table->string('status')->default('active');
            $table->integer('max_users')->default(1);
            $table->integer('max_invoices_month')->default(50);
            $table->integer('max_ai_queries_month')->default(10);
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();

            // Relaciones con empresas y usuarios usando el estilo de nombres de la base de datos
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');
            $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
