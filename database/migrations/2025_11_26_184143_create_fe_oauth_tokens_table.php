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
        Schema::create('fe_oauth_tokens', function (Blueprint $table) {
            $table->id();
            
            // Ambiente del token
            $table->string('ambiente', 20)->index()->comment('sandbox o production');
            
            // Token de acceso
            $table->text('access_token')->comment('Token de acceso OAuth 2.0');
            $table->string('token_type', 50)->default('Bearer')->comment('Tipo de token');
            $table->integer('expires_in')->comment('Segundos hasta expiración');
            $table->timestamp('expires_at')->index()->comment('Timestamp de expiración');
            
            // Refresh token (si se proporciona)
            $table->text('refresh_token')->nullable()->comment('Token de refresco');
            
            // Scope otorgado
            $table->string('scope', 500)->nullable()->comment('Scopes otorgados');
            
            // Control de uso
            $table->boolean('activo')->default(true)->index()->comment('Si está activo');
            $table->integer('uso_contador')->default(0)->comment('Veces que se ha usado');
            $table->timestamp('ultimo_uso')->nullable()->comment('Última vez que se usó');
            
            // Metadatos
            $table->json('metadata')->nullable()->comment('Información adicional');
            
            $table->timestamps();
            
            // Índices
            $table->index(['ambiente', 'activo', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fe_oauth_tokens');
    }
};
