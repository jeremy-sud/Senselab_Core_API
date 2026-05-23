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
        Schema::create('tenant_usages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('tenant_id')->unique();
            $table->integer('active_users_count')->default(1);
            $table->integer('invoices_count_current_month')->default(0);
            $table->integer('ai_queries_count_current_month')->default(0);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_usages');
    }
};
