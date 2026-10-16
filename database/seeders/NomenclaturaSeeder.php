<?php

namespace Database\Seeders;

use App\Models\CuentaContable;
use Illuminate\Database\Seeder;

/**
 * Nomenclatura contable de ForjaPC (las cuentas de primer y segundo nivel son de agrupación).
 */
class NomenclaturaSeeder extends Seeder
{
    public const BANCOS = '1.1.01';

    public const PAYPAL = '1.1.02';

    public const INVENTARIO = '1.1.03';

    public const IVA_CREDITO = '1.1.04';

    public const PROVEEDORES = '2.1.01';

    public const IVA_DEBITO = '2.1.02';

    public const CAPITAL = '3.1.01';

    public const RESULTADOS_ACUMULADOS = '3.1.02';

    public const VENTAS = '4.1.01';

    public const SERVICIO_ARMADO = '4.1.02';

    public const INGRESOS_ENVIO = '4.1.03';

    public const SOBRANTES = '4.2.01';

    public const COSTO_VENTAS = '5.1.01';

    public const COMISIONES = '6.1.01';

    public const MERMAS = '6.1.02';

    public const DEVOLUCIONES = '6.1.03';

    public function run(): void
    {
        $cuentas = [
            ['1', 'Activo', 'ACTIVO', 'DEUDORA', null],
            ['1.1', 'Activo corriente', 'ACTIVO', 'DEUDORA', '1'],
            [self::BANCOS, 'Caja y bancos', 'ACTIVO', 'DEUDORA', '1.1'],
            [self::PAYPAL, 'Fondos en PayPal', 'ACTIVO', 'DEUDORA', '1.1'],
            [self::INVENTARIO, 'Inventario de mercadería', 'ACTIVO', 'DEUDORA', '1.1'],
            [self::IVA_CREDITO, 'IVA crédito fiscal', 'ACTIVO', 'DEUDORA', '1.1'],
            ['2', 'Pasivo', 'PASIVO', 'ACREEDORA', null],
            ['2.1', 'Pasivo corriente', 'PASIVO', 'ACREEDORA', '2'],
            [self::PROVEEDORES, 'Proveedores', 'PASIVO', 'ACREEDORA', '2.1'],
            [self::IVA_DEBITO, 'IVA débito fiscal', 'PASIVO', 'ACREEDORA', '2.1'],
            ['3', 'Patrimonio', 'PATRIMONIO', 'ACREEDORA', null],
            ['3.1', 'Capital contable', 'PATRIMONIO', 'ACREEDORA', '3'],
            [self::CAPITAL, 'Capital', 'PATRIMONIO', 'ACREEDORA', '3.1'],
            [self::RESULTADOS_ACUMULADOS, 'Resultados acumulados', 'PATRIMONIO', 'ACREEDORA', '3.1'],
            ['4', 'Ingresos', 'INGRESO', 'ACREEDORA', null],
            ['4.1', 'Ingresos de operación', 'INGRESO', 'ACREEDORA', '4'],
            [self::VENTAS, 'Ventas de mercadería', 'INGRESO', 'ACREEDORA', '4.1'],
            [self::SERVICIO_ARMADO, 'Servicio de armado', 'INGRESO', 'ACREEDORA', '4.1'],
            [self::INGRESOS_ENVIO, 'Ingresos por envíos', 'INGRESO', 'ACREEDORA', '4.1'],
            ['4.2', 'Otros ingresos', 'INGRESO', 'ACREEDORA', '4'],
            [self::SOBRANTES, 'Sobrantes de inventario', 'INGRESO', 'ACREEDORA', '4.2'],
            ['5', 'Costos', 'COSTO', 'DEUDORA', null],
            ['5.1', 'Costo de ventas', 'COSTO', 'DEUDORA', '5'],
            [self::COSTO_VENTAS, 'Costo de mercadería vendida', 'COSTO', 'DEUDORA', '5.1'],
            ['6', 'Gastos', 'GASTO', 'DEUDORA', null],
            ['6.1', 'Gastos de operación', 'GASTO', 'DEUDORA', '6'],
            [self::COMISIONES, 'Comisiones bancarias y de pasarela', 'GASTO', 'DEUDORA', '6.1'],
            [self::MERMAS, 'Mermas de inventario', 'GASTO', 'DEUDORA', '6.1'],
            [self::DEVOLUCIONES, 'Devoluciones y rebajas sobre ventas', 'GASTO', 'DEUDORA', '6.1'],
        ];

        foreach ($cuentas as [$codigo, $nombre, $tipo, $naturaleza, $padre]) {
            CuentaContable::firstOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'tipo' => $tipo,
                'naturaleza' => $naturaleza,
                'padre_id' => $padre ? CuentaContable::query()->where('codigo', $padre)->value('id') : null,
                'acepta_movimientos' => substr_count($codigo, '.') === 2,
            ]);
        }
    }
}
