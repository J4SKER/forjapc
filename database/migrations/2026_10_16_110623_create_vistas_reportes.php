<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vistas de la base de datos que consultan los reportes y el tablero.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ventas cobradas por día (pedidos con pago aprobado, sin los reembolsados).
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_ventas_diarias AS
            SELECT DATE(p.pagado_at) AS fecha,
                   COUNT(*) AS pedidos,
                   SUM(p.total) AS total,
                   SUM(p.base_imponible) AS base_imponible,
                   SUM(p.iva) AS iva,
                   SUM(CASE WHEN p.metodo_pago = 'PAYPAL' THEN p.total ELSE 0 END) AS total_paypal,
                   SUM(CASE WHEN p.metodo_pago = 'TRANSFERENCIA' THEN p.total ELSE 0 END) AS total_transferencia
            FROM pedidos p
            WHERE p.pagado_at IS NOT NULL AND p.estado <> 'REEMBOLSADO'
            GROUP BY DATE(p.pagado_at)
            SQL);

        // Existencias y valorización del inventario por producto.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_existencias AS
            SELECT pr.id AS producto_id, pr.sku, pr.nombre, c.nombre AS categoria, m.nombre AS marca,
                   pr.existencia, pr.reservado, (pr.existencia - pr.reservado) AS disponible,
                   pr.stock_minimo, pr.costo_promedio,
                   ROUND(pr.existencia * pr.costo_promedio, 2) AS valor,
                   ((pr.existencia - pr.reservado) <= pr.stock_minimo) AS stock_bajo
            FROM productos pr
            JOIN categorias c ON c.id = pr.categoria_id
            JOIN marcas m ON m.id = pr.marca_id
            WHERE pr.deleted_at IS NULL
            SQL);

        // Libro diario: una fila por línea de partida.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_libro_diario AS
            SELECT pa.id AS partida_id, pa.numero, pa.fecha, pe.anio, pe.mes, pa.descripcion, pa.tipo,
                   cu.id AS cuenta_id, cu.codigo, cu.nombre AS cuenta, d.debe, d.haber, d.descripcion AS detalle
            FROM partidas pa
            JOIN periodos_contables pe ON pe.id = pa.periodo_id
            JOIN partida_detalle d ON d.partida_id = pa.id
            JOIN cuentas_contables cu ON cu.id = d.cuenta_id
            SQL);

        // Libro mayor: movimientos por cuenta con su naturaleza para calcular saldos.
        DB::statement(<<<'SQL'
            CREATE OR REPLACE VIEW v_libro_mayor AS
            SELECT cu.id AS cuenta_id, cu.codigo, cu.nombre AS cuenta, cu.tipo, cu.naturaleza,
                   pa.fecha, pa.numero, pa.id AS partida_id, pa.descripcion, d.debe, d.haber,
                   CASE WHEN cu.naturaleza = 'DEUDORA' THEN d.debe - d.haber ELSE d.haber - d.debe END AS efecto
            FROM partida_detalle d
            JOIN partidas pa ON pa.id = d.partida_id
            JOIN cuentas_contables cu ON cu.id = d.cuenta_id
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['v_libro_mayor', 'v_libro_diario', 'v_existencias', 'v_ventas_diarias'] as $vista) {
            DB::statement("DROP VIEW IF EXISTS {$vista}");
        }
    }
};
