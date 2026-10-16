<?php

namespace App\Services;

use App\Enums\EstadoOrdenArmado;
use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\MetodoPago;
use App\Models\CuentaContable;
use App\Models\OrdenArmado;
use App\Models\Pago;
use App\Models\Pedido;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de los reportes y del tablero (usa las vistas v_ventas_diarias, v_existencias,
 * v_libro_diario y v_libro_mayor).
 */
class ReporteService
{
    /**
     * Indicadores del tablero.
     *
     * @return array<string, mixed>
     */
    public function tablero(): array
    {
        $hoy = CarbonImmutable::today();
        $ventas = fn (CarbonImmutable $desde) => DB::table('v_ventas_diarias')->where('fecha', '>=', $desde->toDateString());

        $mes = $ventas($hoy->startOfMonth());
        $pedidosMes = (int) (clone $mes)->sum('pedidos');

        return [
            'ventas_hoy' => (float) $ventas($hoy)->sum('total'),
            'ventas_semana' => (float) $ventas($hoy->startOfWeek())->sum('total'),
            'ventas_mes' => (float) (clone $mes)->sum('total'),
            'pedidos_mes' => $pedidosMes,
            'ticket_promedio' => $pedidosMes > 0 ? round((float) (clone $mes)->sum('total') / $pedidosMes, 2) : 0.0,
            'por_estado' => Pedido::query()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado')->map(fn ($total) => (int) $total),
            'ultimos_dias' => $this->ventasPorDia($hoy->subDays(13), $hoy),
            'stock_bajo' => (int) DB::table('v_existencias')->where('stock_bajo', 1)->count(),
            'pagos_por_verificar' => Pago::query()->where('estado', EstadoPago::Pendiente)->where('metodo', MetodoPago::Transferencia)->whereNotNull('comprobante_ruta')->count(),
            'ordenes_pendientes' => OrdenArmado::query()->whereIn('estado', [EstadoOrdenArmado::Pendiente, EstadoOrdenArmado::Incidencia])->count(),
            'ordenes_en_proceso' => OrdenArmado::query()->whereIn('estado', [EstadoOrdenArmado::EnArmado, EstadoOrdenArmado::EnPruebas])->count(),
            'listos_entrega' => Pedido::query()->where('estado', EstadoPedido::ListoEntrega)->count(),
            'por_vencer' => Pedido::query()->where('estado', EstadoPedido::PendientePago)->where('vence_at', '<', now()->addHours(2))->count(),
        ];
    }

    /**
     * Ventas cobradas por día, incluyendo días sin ventas.
     *
     * @return Collection<int, array{fecha: string, total: float, pedidos: int}>
     */
    public function ventasPorDia(CarbonImmutable $desde, CarbonImmutable $hasta): Collection
    {
        $filas = DB::table('v_ventas_diarias')->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->get()->keyBy('fecha');

        return collect(range(0, (int) $desde->diffInDays($hasta)))->map(function (int $dia) use ($desde, $filas) {
            $fecha = $desde->addDays($dia)->toDateString();

            return ['fecha' => $fecha, 'total' => (float) ($filas[$fecha]->total ?? 0), 'pedidos' => (int) ($filas[$fecha]->pedidos ?? 0)];
        });
    }

    /**
     * Reporte de ventas del período: totales, por método de pago, por categoría y más vendidos.
     *
     * @return array<string, mixed>
     */
    public function ventas(CarbonImmutable $desde, CarbonImmutable $hasta): array
    {
        $rango = [$desde->toDateString(), $hasta->toDateString()];
        $diarias = DB::table('v_ventas_diarias')->whereBetween('fecha', $rango);

        $lineas = DB::table('pedido_items as i')
            ->join('pedidos as p', 'p.id', '=', 'i.pedido_id')
            ->join('productos as pr', 'pr.id', '=', 'i.producto_id')
            ->join('categorias as c', 'c.id', '=', 'pr.categoria_id')
            ->whereNotNull('p.pagado_at')->where('p.estado', '<>', 'REEMBOLSADO')
            ->whereBetween(DB::raw('DATE(p.pagado_at)'), $rango);

        return [
            'resumen' => [
                'pedidos' => (int) (clone $diarias)->sum('pedidos'),
                'total' => (float) (clone $diarias)->sum('total'),
                'base_imponible' => (float) (clone $diarias)->sum('base_imponible'),
                'iva' => (float) (clone $diarias)->sum('iva'),
                'paypal' => (float) (clone $diarias)->sum('total_paypal'),
                'transferencia' => (float) (clone $diarias)->sum('total_transferencia'),
            ],
            'por_dia' => $this->ventasPorDia($desde, $hasta),
            'por_categoria' => (clone $lineas)->selectRaw('c.nombre as categoria, SUM(i.cantidad) as unidades, SUM(i.subtotal) as total, SUM(i.cantidad * i.costo_unitario) as costo')
                ->groupBy('c.nombre')->orderByDesc('total')->get(),
            'mas_vendidos' => (clone $lineas)->selectRaw('pr.sku, pr.nombre, SUM(i.cantidad) as unidades, SUM(i.subtotal) as total')
                ->groupBy('pr.sku', 'pr.nombre')->orderByDesc('unidades')->limit(10)->get(),
        ];
    }

    /**
     * @return Collection<int, object>
     */
    public function libroDiario(CarbonImmutable $desde, CarbonImmutable $hasta): Collection
    {
        return DB::table('v_libro_diario')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('fecha')->orderBy('partida_id')->orderByDesc('debe')
            ->get();
    }

    /**
     * Movimientos de una cuenta con saldo inicial y saldo acumulado.
     *
     * @return array{cuenta: CuentaContable, saldo_inicial: float, movimientos: Collection<int, object>, saldo_final: float}
     */
    public function libroMayor(CuentaContable $cuenta, CarbonImmutable $desde, CarbonImmutable $hasta): array
    {
        $saldoInicial = (float) DB::table('v_libro_mayor')->where('cuenta_id', $cuenta->id)->where('fecha', '<', $desde->toDateString())->sum('efecto');
        $saldo = $saldoInicial;

        $movimientos = DB::table('v_libro_mayor')->where('cuenta_id', $cuenta->id)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('fecha')->orderBy('partida_id')->get()
            ->map(function (object $fila) use (&$saldo) {
                $saldo = round($saldo + (float) $fila->efecto, 2);
                $fila->saldo = $saldo;

                return $fila;
            });

        return ['cuenta' => $cuenta, 'saldo_inicial' => $saldoInicial, 'movimientos' => $movimientos, 'saldo_final' => $saldo];
    }

    /**
     * Balance de comprobación: sumas y saldos de cada cuenta con movimiento hasta la fecha.
     *
     * @return array{cuentas: Collection<int, object>, totales: array<string, float>}
     */
    public function balanceComprobacion(CarbonImmutable $hasta): array
    {
        $cuentas = DB::table('v_libro_mayor')->where('fecha', '<=', $hasta->toDateString())
            ->selectRaw('cuenta_id, codigo, cuenta, tipo, naturaleza, SUM(debe) as debe, SUM(haber) as haber')
            ->groupBy('cuenta_id', 'codigo', 'cuenta', 'tipo', 'naturaleza')
            ->orderBy('codigo')->get()
            ->map(function (object $fila) {
                $diferencia = round((float) $fila->debe - (float) $fila->haber, 2);
                $fila->saldo_deudor = $diferencia > 0 ? $diferencia : 0.0;
                $fila->saldo_acreedor = $diferencia < 0 ? -$diferencia : 0.0;

                return $fila;
            });

        return ['cuentas' => $cuentas, 'totales' => [
            'debe' => round((float) $cuentas->sum('debe'), 2),
            'haber' => round((float) $cuentas->sum('haber'), 2),
            'saldo_deudor' => round((float) $cuentas->sum('saldo_deudor'), 2),
            'saldo_acreedor' => round((float) $cuentas->sum('saldo_acreedor'), 2),
        ]];
    }

    /**
     * Estado de resultados del período: ingresos, costo de ventas, gastos y utilidad.
     *
     * @return array<string, mixed>
     */
    public function estadoResultados(CarbonImmutable $desde, CarbonImmutable $hasta): array
    {
        $porTipo = fn (string $tipo) => DB::table('v_libro_mayor')->where('tipo', $tipo)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->selectRaw('codigo, cuenta, SUM(efecto) as monto')->groupBy('codigo', 'cuenta')->orderBy('codigo')->get();

        $ingresos = $porTipo('INGRESO');
        $costos = $porTipo('COSTO');
        $gastos = $porTipo('GASTO');

        $totalIngresos = round((float) $ingresos->sum('monto'), 2);
        $totalCostos = round((float) $costos->sum('monto'), 2);
        $totalGastos = round((float) $gastos->sum('monto'), 2);

        return [
            'ingresos' => $ingresos,
            'costos' => $costos,
            'gastos' => $gastos,
            'total_ingresos' => $totalIngresos,
            'total_costos' => $totalCostos,
            'utilidad_bruta' => round($totalIngresos - $totalCostos, 2),
            'total_gastos' => $totalGastos,
            'utilidad_neta' => round($totalIngresos - $totalCostos - $totalGastos, 2),
        ];
    }
}
