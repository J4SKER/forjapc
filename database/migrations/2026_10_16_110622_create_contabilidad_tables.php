<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Área de contabilidad (4 tablas): nomenclatura de cuentas, períodos, partidas y su detalle.
 * La base de datos rechaza partidas descuadradas y no permite modificarlas ni borrarlas.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuentas_contables', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 120);
            $table->enum('tipo', ['ACTIVO', 'PASIVO', 'PATRIMONIO', 'INGRESO', 'COSTO', 'GASTO']);
            $table->enum('naturaleza', ['DEUDORA', 'ACREEDORA']);
            $table->foreignId('padre_id')->nullable()->constrained('cuentas_contables');
            $table->boolean('acepta_movimientos')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('periodos_contables', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->enum('estado', ['ABIERTO', 'CERRADO'])->default('ABIERTO');
            $table->foreignId('cerrado_por')->nullable()->constrained('usuarios');
            $table->timestamp('cerrado_at')->nullable();
            $table->timestamps();
            $table->unique(['anio', 'mes']);
        });

        DB::statement('ALTER TABLE periodos_contables ADD CONSTRAINT chk_periodo_mes CHECK (mes BETWEEN 1 AND 12)');

        Schema::create('partidas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero');
            $table->date('fecha')->index();
            $table->foreignId('periodo_id')->constrained('periodos_contables');
            $table->string('descripcion', 255);
            $table->enum('tipo', ['AUTOMATICA', 'MANUAL', 'REVERSION']);
            $table->string('origen_tipo', 40)->nullable();
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->foreignId('revierte_partida_id')->nullable()->unique()->constrained('partidas');
            $table->decimal('total_debe', 14, 2);
            $table->decimal('total_haber', 14, 2);
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['periodo_id', 'numero']);
            $table->index(['origen_tipo', 'origen_id']);
        });

        DB::statement('ALTER TABLE partidas ADD CONSTRAINT chk_partida_cuadra CHECK (total_debe = total_haber AND total_debe > 0)');

        Schema::create('partida_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_id')->constrained('partidas')->restrictOnDelete();
            $table->foreignId('cuenta_id')->constrained('cuentas_contables');
            $table->decimal('debe', 14, 2)->default(0);
            $table->decimal('haber', 14, 2)->default(0);
            $table->string('descripcion', 255)->nullable();
            $table->index(['cuenta_id', 'partida_id']);
        });

        DB::statement('ALTER TABLE partida_detalle ADD CONSTRAINT chk_detalle_un_lado CHECK (debe >= 0 AND haber >= 0 AND ((debe > 0 AND haber = 0) OR (debe = 0 AND haber > 0)))');

        $this->crearDisparadores();
    }

    /**
     * Disparadores que hacen cumplir en la base de datos las reglas contables:
     * el detalle debe sumar lo mismo que el encabezado y nada se modifica ni se borra.
     */
    private function crearDisparadores(): void
    {
        // Cada línea nueva no puede hacer que el detalle supere los totales del encabezado.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_partida_detalle_cuadra BEFORE INSERT ON partida_detalle FOR EACH ROW
            BEGIN
                DECLARE v_debe DECIMAL(14,2);
                DECLARE v_haber DECIMAL(14,2);
                DECLARE v_total_debe DECIMAL(14,2);
                DECLARE v_total_haber DECIMAL(14,2);
                DECLARE v_acepta TINYINT;

                SELECT acepta_movimientos INTO v_acepta FROM cuentas_contables WHERE id = NEW.cuenta_id;
                IF v_acepta = 0 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La cuenta es de agrupación y no acepta movimientos.';
                END IF;

                SELECT COALESCE(SUM(debe), 0), COALESCE(SUM(haber), 0) INTO v_debe, v_haber FROM partida_detalle WHERE partida_id = NEW.partida_id;
                SELECT total_debe, total_haber INTO v_total_debe, v_total_haber FROM partidas WHERE id = NEW.partida_id;

                IF v_debe + NEW.debe > v_total_debe OR v_haber + NEW.haber > v_total_haber THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La partida no cuadra: el detalle excede los totales del encabezado.';
                END IF;
            END
            SQL);

        foreach (['partidas', 'partida_detalle'] as $tabla) {
            DB::unprepared("CREATE TRIGGER trg_{$tabla}_inmutable_upd BEFORE UPDATE ON {$tabla} FOR EACH ROW
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Las partidas contables no se modifican; registre una partida de reversión.'");
            DB::unprepared("CREATE TRIGGER trg_{$tabla}_inmutable_del BEFORE DELETE ON {$tabla} FOR EACH ROW
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Las partidas contables no se borran; registre una partida de reversión.'");
        }

        // Tampoco se registran partidas en un período cerrado.
        DB::unprepared(<<<'SQL'
            CREATE TRIGGER trg_partidas_periodo_abierto BEFORE INSERT ON partidas FOR EACH ROW
            BEGIN
                IF (SELECT estado FROM periodos_contables WHERE id = NEW.periodo_id) = 'CERRADO' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El período contable está cerrado.';
                END IF;
            END
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['trg_partida_detalle_cuadra', 'trg_partidas_inmutable_upd', 'trg_partidas_inmutable_del', 'trg_partida_detalle_inmutable_upd', 'trg_partida_detalle_inmutable_del', 'trg_partidas_periodo_abierto'] as $disparador) {
            DB::unprepared("DROP TRIGGER IF EXISTS {$disparador}");
        }

        Schema::dropIfExists('partida_detalle');
        Schema::dropIfExists('partidas');
        Schema::dropIfExists('periodos_contables');
        Schema::dropIfExists('cuentas_contables');
    }
};
