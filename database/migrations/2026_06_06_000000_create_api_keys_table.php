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
        Schema::create('api_keys', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('empresa_id');
            $table->string('name', 100);
            $table->string('prefix', 16);
            $table->string('token_hash', 64)->unique();
            $table->enum('environment', ['live', 'sandbox'])->default('sandbox');
            $table->boolean('activo')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('empresa_id')->references('id')->on('empresas')->onDelete('cascade');

            // Indices
            $table->index(['empresa_id', 'activo'], 'idx_api_keys_empresa_activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
