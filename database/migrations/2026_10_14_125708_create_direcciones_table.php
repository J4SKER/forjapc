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
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('alias', 40);
            $table->string('nombre_receptor', 120);
            $table->string('telefono', 20);
            $table->foreignId('departamento_id')->constrained('departamentos')->restrictOnDelete();
            $table->string('municipio', 80);
            $table->string('direccion', 255);
            $table->string('referencias', 255)->nullable();
            $table->boolean('es_principal')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};
