<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Área de catálogo (17 tablas): categorías, marcas, sockets, productos, imágenes,
 * especificaciones técnicas por tipo de componente y reseñas.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('slug', 90)->unique();
            $table->string('icono', 30)->default('paquete');
            $table->enum('tipo_componente', ['cpu', 'placa', 'ram', 'gpu', 'almacenamiento', 'fuente', 'gabinete', 'disipador', 'monitor'])->nullable()->unique();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->string('slug', 70)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('sockets', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20)->unique();
            $table->timestamps();
        });

        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('marca_id')->constrained('marcas')->restrictOnDelete();
            $table->string('sku', 40)->unique();
            $table->string('nombre', 160);
            $table->string('slug', 180)->unique();
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->decimal('precio_anterior', 10, 2)->nullable();
            $table->unsignedTinyInteger('garantia_meses')->default(12);
            $table->unsignedInteger('existencia')->default(0);
            $table->unsignedInteger('reservado')->default(0);
            $table->decimal('costo_promedio', 12, 4)->default(0);
            $table->unsignedInteger('stock_minimo')->default(2);
            $table->unsignedSmallInteger('consumo_watts')->default(0);
            $table->boolean('destacado')->default(false);
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['categoria_id', 'activo']);
            $table->fullText(['nombre', 'descripcion']);
        });

        DB::statement('ALTER TABLE productos ADD CONSTRAINT chk_productos_precio CHECK (precio > 0)');
        DB::statement('ALTER TABLE productos ADD CONSTRAINT chk_productos_reservado CHECK (reservado <= existencia)');

        Schema::create('producto_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('ruta');
            $table->unsignedTinyInteger('orden')->default(0);
            $table->timestamps();
        });

        Schema::create('especificaciones_cpu', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->foreignId('socket_id')->constrained('sockets');
            $table->unsignedTinyInteger('nucleos');
            $table->unsignedTinyInteger('hilos');
            $table->decimal('frecuencia_base_ghz', 3, 1);
            $table->decimal('frecuencia_turbo_ghz', 3, 1)->nullable();
            $table->unsignedSmallInteger('tdp_w');
            $table->enum('tipo_memoria', ['DDR4', 'DDR5', 'DDR4/DDR5']);
            $table->boolean('graficos_integrados')->default(false);
            $table->boolean('incluye_disipador')->default(false);
        });

        Schema::create('especificaciones_placa_base', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->foreignId('socket_id')->constrained('sockets');
            $table->string('chipset', 20);
            $table->enum('formato', ['Mini-ITX', 'Micro-ATX', 'ATX', 'E-ATX']);
            $table->enum('tipo_memoria', ['DDR4', 'DDR5']);
            $table->unsignedTinyInteger('ranuras_ram');
            $table->unsignedSmallInteger('ram_max_gb');
            $table->unsignedTinyInteger('slots_m2');
            $table->unsignedTinyInteger('puertos_sata');
            $table->boolean('wifi')->default(false);
        });

        Schema::create('especificaciones_ram', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->enum('tipo', ['DDR4', 'DDR5']);
            $table->unsignedSmallInteger('capacidad_total_gb');
            $table->unsignedTinyInteger('modulos');
            $table->unsignedSmallInteger('velocidad_mhz');
            $table->unsignedTinyInteger('latencia_cl')->nullable();
        });

        Schema::create('especificaciones_gpu', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->string('chipset', 40);
            $table->unsignedTinyInteger('vram_gb');
            $table->unsignedSmallInteger('longitud_mm');
            $table->unsignedSmallInteger('tdp_w');
            $table->unsignedSmallInteger('fuente_recomendada_w');
            $table->string('conectores_energia', 40);
        });

        Schema::create('especificaciones_almacenamiento', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->enum('tipo', ['SSD NVMe', 'SSD SATA', 'HDD']);
            $table->enum('formato', ['M.2', '2.5"', '3.5"']);
            $table->unsignedInteger('capacidad_gb');
            $table->unsignedSmallInteger('lectura_mbps')->nullable();
            $table->unsignedSmallInteger('escritura_mbps')->nullable();
        });

        Schema::create('especificaciones_fuente', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->unsignedSmallInteger('potencia_w');
            $table->string('certificacion', 30);
            $table->enum('modular', ['No', 'Semi', 'Total']);
            $table->enum('formato', ['ATX', 'SFX']);
        });

        Schema::create('especificaciones_gabinete', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->enum('formato_max_placa', ['Mini-ITX', 'Micro-ATX', 'ATX', 'E-ATX']);
            $table->unsignedSmallInteger('gpu_max_mm');
            $table->unsignedSmallInteger('disipador_max_mm');
            $table->unsignedSmallInteger('radiador_max_mm')->default(0);
            $table->enum('formato_fuente', ['ATX', 'SFX']);
            $table->unsignedTinyInteger('bahias_25')->default(0);
            $table->unsignedTinyInteger('bahias_35')->default(0);
        });

        Schema::create('especificaciones_disipador', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->enum('tipo', ['Aire', 'Líquida']);
            $table->unsignedSmallInteger('altura_mm')->default(0);
            $table->unsignedSmallInteger('radiador_mm')->default(0);
            $table->unsignedSmallInteger('tdp_max_w');
        });

        Schema::create('disipador_socket', function (Blueprint $table) {
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('socket_id')->constrained('sockets')->cascadeOnDelete();
            $table->primary(['producto_id', 'socket_id']);
        });

        Schema::create('especificaciones_monitor', function (Blueprint $table) {
            $table->foreignId('producto_id')->primary()->constrained('productos')->cascadeOnDelete();
            $table->decimal('tamano_pulgadas', 4, 1);
            $table->string('resolucion', 20);
            $table->unsignedSmallInteger('frecuencia_hz');
            $table->enum('panel', ['IPS', 'VA', 'TN', 'OLED']);
            $table->decimal('tiempo_respuesta_ms', 3, 1);
        });

        Schema::create('resenas_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->unsignedTinyInteger('calificacion');
            $table->string('titulo', 120);
            $table->text('comentario');
            $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])->default('PENDIENTE')->index();
            $table->boolean('compra_verificada')->default(false);
            $table->timestamps();
            $table->unique(['producto_id', 'usuario_id']);
        });

        DB::statement('ALTER TABLE resenas_producto ADD CONSTRAINT chk_resenas_calificacion CHECK (calificacion BETWEEN 1 AND 5)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ([
            'resenas_producto', 'especificaciones_monitor', 'disipador_socket', 'especificaciones_disipador',
            'especificaciones_gabinete', 'especificaciones_fuente', 'especificaciones_almacenamiento',
            'especificaciones_gpu', 'especificaciones_ram', 'especificaciones_placa_base', 'especificaciones_cpu',
            'producto_imagenes', 'productos', 'sockets', 'marcas', 'categorias',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
