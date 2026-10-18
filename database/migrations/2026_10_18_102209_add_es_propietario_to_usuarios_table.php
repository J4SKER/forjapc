<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Propietario: administrador con permiso exclusivo para nombrar o modificar administradores.
 * Solo se asigna por consola (forjapc:crear-admin --propietario), nunca desde la web.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('es_propietario')->default(false)->after('rol');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('es_propietario');
        });
    }
};
