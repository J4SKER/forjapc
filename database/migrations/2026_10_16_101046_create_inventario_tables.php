<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Área de inventario y compras (4 tablas): proveedores, compras, su detalle y el kardex.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('nit', 15)->unique();
            $table->string('contacto', 120)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('direccion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->string('serie_factura', 20)->nullable();
            $table->string('numero_factura', 40);
            $table->date('fecha_factura');
            $table->enum('estado', ['BORRADOR', 'RECIBIDA', 'ANULADA'])->default('BORRADOR')->index();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('iva', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notas')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->foreignId('recibida_por')->nullable()->constrained('usuarios');
            $table->timestamp('recibida_at')->nullable();
            $table->timestamps();
            $table->unique(['proveedor_id', 'serie_factura', 'numero_factura']);
        });

        Schema::create('compra_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('costo_unitario', 12, 4);
            $table->decimal('subtotal', 12, 2);
        });

        DB::statement('ALTER TABLE compra_detalle ADD CONSTRAINT chk_compra_detalle CHECK (cantidad > 0 AND costo_unitario > 0)');

        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->enum('tipo', ['ENTRADA_COMPRA', 'SALIDA_VENTA', 'AJUSTE_ENTRADA', 'AJUSTE_SALIDA', 'DEVOLUCION']);
            $table->unsignedInteger('cantidad');
            $table->integer('existencia_anterior');
            $table->integer('existencia_nueva');
            $table->decimal('costo_unitario', 12, 4);
            $table->decimal('costo_promedio_anterior', 12, 4);
            $table->decimal('costo_promedio_nuevo', 12, 4);
            $table->string('referencia_tipo', 40)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('motivo', 255)->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['producto_id', 'id']);
            $table->index(['referencia_tipo', 'referencia_id']);
        });

        DB::statement('ALTER TABLE movimientos_inventario ADD CONSTRAINT chk_movimientos CHECK (cantidad > 0 AND existencia_nueva >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('compra_detalle');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('proveedores');
    }
};
