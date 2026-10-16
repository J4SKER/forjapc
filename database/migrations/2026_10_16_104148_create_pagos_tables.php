<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Área de pagos (3 tablas): pagos, reembolsos y notificaciones de la pasarela.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->restrictOnDelete();
            $table->enum('metodo', ['PAYPAL', 'TRANSFERENCIA']);
            $table->enum('estado', ['PENDIENTE', 'APROBADO', 'RECHAZADO'])->default('PENDIENTE')->index();
            $table->decimal('monto', 12, 2);
            $table->decimal('monto_usd', 12, 2)->nullable();
            $table->decimal('tipo_cambio', 10, 5)->nullable();
            $table->decimal('comision', 12, 2)->default(0);
            $table->string('referencia_externa', 80)->nullable()->index();
            $table->string('banco', 60)->nullable();
            $table->string('numero_boleta', 60)->nullable();
            $table->string('comprobante_ruta')->nullable();
            $table->foreignId('verificado_por')->nullable()->constrained('usuarios');
            $table->timestamp('verificado_at')->nullable();
            $table->string('motivo_rechazo', 255)->nullable();
            $table->json('datos')->nullable();
            $table->timestamps();
        });

        // Un solo pago aprobado por pedido: columna generada con índice único (MySQL ignora los NULL).
        DB::statement("ALTER TABLE pagos ADD COLUMN pedido_aprobado_id BIGINT UNSIGNED GENERATED ALWAYS AS (IF(estado = 'APROBADO', pedido_id, NULL)) STORED");
        DB::statement('ALTER TABLE pagos ADD UNIQUE INDEX pagos_un_aprobado_por_pedido (pedido_aprobado_id)');
        DB::statement('ALTER TABLE pagos ADD CONSTRAINT chk_pagos_monto CHECK (monto > 0 AND comision >= 0)');

        Schema::create('reembolsos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->restrictOnDelete();
            $table->decimal('monto', 12, 2);
            $table->string('motivo', 255);
            $table->string('referencia_externa', 80)->nullable();
            $table->boolean('devuelve_inventario')->default(false);
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->timestamp('created_at')->useCurrent();
        });

        DB::statement('ALTER TABLE reembolsos ADD CONSTRAINT chk_reembolsos_monto CHECK (monto > 0)');

        Schema::create('eventos_webhook', function (Blueprint $table) {
            $table->id();
            $table->string('proveedor', 20)->default('PAYPAL');
            $table->string('evento_id', 80);
            $table->string('tipo', 80);
            $table->json('payload');
            $table->timestamp('procesado_at')->nullable();
            $table->string('error', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['proveedor', 'evento_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos_webhook');
        Schema::dropIfExists('reembolsos');
        Schema::dropIfExists('pagos');
    }
};
