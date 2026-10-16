<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Área de ventas y taller (7 tablas, junto con departamentos): carritos, pedidos,
 * líneas, historial y transiciones de estado, y órdenes de armado.
 */
return new class extends Migration
{
    private const ESTADOS = ['PENDIENTE_PAGO', 'PAGADO', 'EN_ARMADO', 'EN_PREPARACION', 'LISTO_ENTREGA', 'ENVIADO', 'ENTREGADO', 'CANCELADO', 'REEMBOLSADO'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->string('sesion_id', 100)->nullable()->index();
            $table->timestamps();
        });

        Schema::create('carrito_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->cascadeOnDelete();
            $table->enum('tipo', ['PRODUCTO', 'CONFIGURACION']);
            $table->foreignId('producto_id')->nullable()->constrained('productos')->cascadeOnDelete();
            $table->unsignedSmallInteger('cantidad')->default(1);
            $table->string('nombre', 120)->nullable();
            $table->json('componentes')->nullable();
            $table->boolean('incluye_armado')->default(false);
            $table->timestamps();
        });

        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->enum('estado', self::ESTADOS)->default('PENDIENTE_PAGO')->index();
            $table->enum('metodo_entrega', ['ENVIO', 'RECOGER']);
            $table->foreignId('departamento_id')->nullable()->constrained('departamentos');
            $table->json('direccion_envio')->nullable();
            $table->string('nombre_facturacion', 120);
            $table->string('nit', 15)->default('CF');
            $table->string('telefono', 20);
            $table->enum('metodo_pago', ['PAYPAL', 'TRANSFERENCIA']);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('costo_envio', 12, 2)->default(0);
            $table->decimal('costo_armado', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('base_imponible', 12, 2);
            $table->decimal('iva', 12, 2);
            $table->string('mensajeria', 60)->nullable();
            $table->string('numero_guia', 60)->nullable();
            $table->text('notas')->nullable();
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->timestamp('vence_at')->nullable()->index();
            $table->timestamp('pagado_at')->nullable();
            $table->timestamp('entregado_at')->nullable();
            $table->timestamp('cancelado_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pedidos ADD CONSTRAINT chk_pedidos_total CHECK (total = subtotal + costo_envio + costo_armado AND total = base_imponible + iva AND total > 0)');

        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('descripcion', 180);
            $table->string('grupo_configuracion', 20)->nullable()->index();
            $table->string('nombre_configuracion', 120)->nullable();
            $table->boolean('con_armado')->default(false);
            $table->unsignedSmallInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('costo_unitario', 12, 4)->nullable();
            $table->decimal('subtotal', 12, 2);
        });

        DB::statement('ALTER TABLE pedido_items ADD CONSTRAINT chk_pedido_items CHECK (cantidad > 0 AND subtotal = ROUND(cantidad * precio_unitario, 2))');

        Schema::create('transiciones_estado_pedido', function (Blueprint $table) {
            $table->enum('estado_origen', self::ESTADOS);
            $table->enum('estado_destino', self::ESTADOS);
            $table->primary(['estado_origen', 'estado_destino']);
        });

        Schema::create('pedido_historial_estados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->enum('estado_anterior', self::ESTADOS)->nullable();
            $table->enum('estado_nuevo', self::ESTADOS);
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->string('comentario', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ordenes_armado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->string('grupo_configuracion', 20);
            $table->string('nombre_configuracion', 120);
            $table->enum('estado', ['PENDIENTE', 'EN_ARMADO', 'EN_PRUEBAS', 'COMPLETADA', 'INCIDENCIA'])->default('PENDIENTE')->index();
            $table->foreignId('tecnico_id')->nullable()->constrained('usuarios');
            $table->json('checklist')->nullable();
            $table->json('pruebas')->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('iniciada_at')->nullable();
            $table->timestamp('completada_at')->nullable();
            $table->timestamps();
            $table->unique(['pedido_id', 'grupo_configuracion']);
        });

        $this->sembrarTransiciones();
    }

    /**
     * Transiciones válidas del pedido; el servicio de pedidos rechaza cualquier otra.
     */
    private function sembrarTransiciones(): void
    {
        $transiciones = [
            'PENDIENTE_PAGO' => ['PAGADO', 'CANCELADO'],
            'PAGADO' => ['EN_ARMADO', 'EN_PREPARACION', 'REEMBOLSADO'],
            'EN_ARMADO' => ['LISTO_ENTREGA', 'REEMBOLSADO'],
            'EN_PREPARACION' => ['LISTO_ENTREGA', 'REEMBOLSADO'],
            'LISTO_ENTREGA' => ['ENVIADO', 'ENTREGADO', 'REEMBOLSADO'],
            'ENVIADO' => ['ENTREGADO'],
            'ENTREGADO' => ['REEMBOLSADO'],
        ];

        foreach ($transiciones as $origen => $destinos) {
            foreach ($destinos as $destino) {
                DB::table('transiciones_estado_pedido')->insert(['estado_origen' => $origen, 'estado_destino' => $destino]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['ordenes_armado', 'pedido_historial_estados', 'transiciones_estado_pedido', 'pedido_items', 'pedidos', 'carrito_items', 'carritos'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
