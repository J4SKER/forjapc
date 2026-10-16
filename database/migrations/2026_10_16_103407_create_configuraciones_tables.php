<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Área del configurador (2 tablas): configuraciones de PC y sus componentes.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('codigo', 12)->unique();
            $table->string('nombre', 80);
            $table->boolean('incluye_armado')->default(true);
            $table->timestamps();
        });

        Schema::create('configuracion_componentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuracion_id')->constrained('configuraciones')->cascadeOnDelete();
            $table->enum('tipo', ['cpu', 'placa', 'ram', 'gpu', 'almacenamiento', 'fuente', 'gabinete', 'disipador']);
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedTinyInteger('cantidad')->default(1);
            $table->unique(['configuracion_id', 'producto_id']);
        });

        DB::statement('ALTER TABLE configuracion_componentes ADD CONSTRAINT chk_config_cantidad CHECK (cantidad BETWEEN 1 AND 8)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion_componentes');
        Schema::dropIfExists('configuraciones');
    }
};
