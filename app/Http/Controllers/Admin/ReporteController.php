<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuentaContable;
use App\Services\ReporteService;
use App\Support\Csv;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reportes: ventas, valorización del inventario, libro diario, libro mayor,
 * balance de comprobación y estado de resultados, con exportación a CSV.
 */
class ReporteController extends Controller
{
    public function __construct(private ReporteService $reportes) {}

    public function ventas(Request $request): View|StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $datos = $this->reportes->ventas($desde, $hasta);

        if ($request->input('formato') === 'csv') {
            return Csv::descargar('ventas', ['Fecha', 'Pedidos', 'Total (Q)'], $datos['por_dia']->map(fn ($d) => [$d['fecha'], $d['pedidos'], number_format($d['total'], 2, '.', '')]));
        }

        return view('admin.reportes.ventas', $datos + compact('desde', 'hasta'));
    }

    public function inventario(Request $request): View|StreamedResponse
    {
        $existencias = DB::table('v_existencias')->orderBy('categoria')->orderBy('nombre')->get();

        if ($request->input('formato') === 'csv') {
            return Csv::descargar('valorizacion-inventario', ['SKU', 'Producto', 'Categoría', 'Marca', 'Existencia', 'Reservado', 'Costo promedio', 'Valor'],
                $existencias->map(fn ($e) => [$e->sku, $e->nombre, $e->categoria, $e->marca, $e->existencia, $e->reservado, $e->costo_promedio, $e->valor]));
        }

        return view('admin.reportes.inventario', [
            'existencias' => $existencias,
            'porCategoria' => $existencias->groupBy('categoria')->map(fn ($g) => ['unidades' => $g->sum('existencia'), 'valor' => $g->sum('valor')]),
        ]);
    }

    public function libroDiario(Request $request): View|StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $lineas = $this->reportes->libroDiario($desde, $hasta);

        if ($request->input('formato') === 'csv') {
            return Csv::descargar('libro-diario', ['Fecha', 'Partida', 'Descripción', 'Cuenta', 'Nombre', 'Debe', 'Haber'],
                $lineas->map(fn ($l) => [$l->fecha, $l->anio.'-'.str_pad((string) $l->mes, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $l->numero, 4, '0', STR_PAD_LEFT), $l->descripcion, $l->codigo, $l->cuenta, $l->debe, $l->haber]));
        }

        return view('admin.reportes.libro-diario', ['partidas' => $lineas->groupBy('partida_id'), 'desde' => $desde, 'hasta' => $hasta,
            'totalDebe' => $lineas->sum('debe'), 'totalHaber' => $lineas->sum('haber')]);
    }

    public function libroMayor(Request $request): View|StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $cuentas = CuentaContable::query()->where('acepta_movimientos', true)->orderBy('codigo')->get(['id', 'codigo', 'nombre']);
        $cuenta = CuentaContable::find($request->integer('cuenta')) ?? $cuentas->first();
        $mayor = $cuenta ? $this->reportes->libroMayor($cuenta, $desde, $hasta) : null;

        if ($mayor && $request->input('formato') === 'csv') {
            return Csv::descargar("libro-mayor-{$cuenta->codigo}", ['Fecha', 'Descripción', 'Debe', 'Haber', 'Saldo'],
                $mayor['movimientos']->map(fn ($m) => [$m->fecha, $m->descripcion, $m->debe, $m->haber, $m->saldo]));
        }

        return view('admin.reportes.libro-mayor', compact('cuentas', 'mayor', 'desde', 'hasta'));
    }

    public function balance(Request $request): View|StreamedResponse
    {
        $hasta = $request->filled('hasta') ? CarbonImmutable::parse($request->input('hasta')) : CarbonImmutable::today();
        $balance = $this->reportes->balanceComprobacion($hasta);

        if ($request->input('formato') === 'csv') {
            return Csv::descargar('balance-comprobacion', ['Cuenta', 'Nombre', 'Debe', 'Haber', 'Saldo deudor', 'Saldo acreedor'],
                $balance['cuentas']->map(fn ($c) => [$c->codigo, $c->cuenta, $c->debe, $c->haber, $c->saldo_deudor, $c->saldo_acreedor]));
        }

        return view('admin.reportes.balance', $balance + ['hasta' => $hasta]);
    }

    public function resultados(Request $request): View|StreamedResponse
    {
        [$desde, $hasta] = $this->rango($request);
        $estado = $this->reportes->estadoResultados($desde, $hasta);

        if ($request->input('formato') === 'csv') {
            $filas = collect()
                ->concat($estado['ingresos']->map(fn ($c) => ['Ingresos', $c->codigo, $c->cuenta, $c->monto]))
                ->concat($estado['costos']->map(fn ($c) => ['Costos', $c->codigo, $c->cuenta, $c->monto]))
                ->concat($estado['gastos']->map(fn ($c) => ['Gastos', $c->codigo, $c->cuenta, $c->monto]))
                ->push(['Utilidad neta', '', '', $estado['utilidad_neta']]);

            return Csv::descargar('estado-de-resultados', ['Sección', 'Cuenta', 'Nombre', 'Monto'], $filas);
        }

        return view('admin.reportes.resultados', $estado + compact('desde', 'hasta'));
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function rango(Request $request): array
    {
        $desde = $request->filled('desde') ? CarbonImmutable::parse($request->input('desde')) : CarbonImmutable::today()->startOfMonth();
        $hasta = $request->filled('hasta') ? CarbonImmutable::parse($request->input('hasta')) : CarbonImmutable::today();

        return $desde->greaterThan($hasta) ? [$hasta, $desde] : [$desde, $hasta];
    }
}
