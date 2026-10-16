<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CuentaContable;
use App\Models\Partida;
use App\Models\PeriodoContable;
use App\Services\BitacoraService;
use App\Services\ContabilidadService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Contabilidad: partidas (automáticas, manuales y de reversión), períodos y nomenclatura.
 */
class ContabilidadController extends Controller
{
    public function __construct(private ContabilidadService $contabilidad, private BitacoraService $bitacora) {}

    public function partidas(Request $request): View
    {
        $periodo = $request->filled('periodo') ? PeriodoContable::find($request->integer('periodo')) : null;

        return view('admin.contabilidad.partidas', [
            'partidas' => Partida::query()->with(['periodo', 'usuario:id,nombre', 'reversion:id,revierte_partida_id'])
                ->when($periodo, fn (Builder $q) => $q->where('periodo_id', $periodo->id))
                ->when($request->filled('tipo'), fn (Builder $q) => $q->where('tipo', $request->string('tipo')->value()))
                ->when($request->filled('q'), fn (Builder $q) => $q->where('descripcion', 'like', '%'.$request->string('q')->value().'%'))
                ->latest('fecha')->latest('id')->paginate(25)->withQueryString(),
            'periodos' => PeriodoContable::query()->orderByDesc('anio')->orderByDesc('mes')->get(),
        ]);
    }

    public function show(Partida $partida): View
    {
        return view('admin.contabilidad.partida', ['partida' => $partida->load(['detalle.cuenta', 'periodo', 'usuario', 'revierte.periodo', 'reversion.periodo'])]);
    }

    public function create(): View
    {
        return view('admin.contabilidad.nueva-partida', [
            'cuentas' => CuentaContable::query()->where('acepta_movimientos', true)->where('activo', true)->orderBy('codigo')->get(['id', 'codigo', 'nombre']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['lineas' => collect($request->input('lineas', []))->filter(fn ($l) => filled($l['codigo'] ?? null))->values()->all()]);

        $datos = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'descripcion' => ['required', 'string', 'min:5', 'max:255'],
            'lineas' => ['required', 'array', 'min:2'],
            'lineas.*.codigo' => ['required', Rule::exists('cuentas_contables', 'codigo')->where('acepta_movimientos', true)],
            'lineas.*.debe' => ['nullable', 'numeric', 'min:0'],
            'lineas.*.haber' => ['nullable', 'numeric', 'min:0'],
        ], [], ['lineas' => 'líneas de la partida', 'lineas.*.codigo' => 'cuenta', 'lineas.*.debe' => 'debe', 'lineas.*.haber' => 'haber']);

        try {
            $partida = $this->contabilidad->registrar(
                $datos['descripcion'],
                array_map(fn ($l) => [$l['codigo'], (float) ($l['debe'] ?? 0), (float) ($l['haber'] ?? 0)], $datos['lineas']),
                'MANUAL',
                CarbonImmutable::parse($datos['fecha']),
            );
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['lineas' => $e->getMessage()]);
        }

        $this->bitacora->registrar('partida_manual', $partida, null, ['descripcion' => $partida->descripcion, 'total' => $partida->total_debe]);

        return redirect()->route('admin.contabilidad.partidas.show', $partida)->with('estado', 'Partida manual registrada.');
    }

    public function revertir(Request $request, Partida $partida): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:200']]);

        try {
            $reversion = $this->contabilidad->revertir($partida, $datos['motivo']);
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        $this->bitacora->registrar('partida_revertida', $partida, null, ['reversion_id' => $reversion->id, 'motivo' => $datos['motivo']]);

        return redirect()->route('admin.contabilidad.partidas.show', $reversion)->with('estado', 'Partida revertida.');
    }

    public function periodos(): View
    {
        return view('admin.contabilidad.periodos', [
            'periodos' => PeriodoContable::query()->withCount('partidas')->with('cerradoPor:id,nombre')->orderByDesc('anio')->orderByDesc('mes')->get(),
        ]);
    }

    public function cerrarPeriodo(Request $request, PeriodoContable $periodo): RedirectResponse
    {
        try {
            $this->contabilidad->cerrarPeriodo($periodo, $request->user());
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        $this->bitacora->registrar('periodo_cerrado', $periodo, null, ['periodo' => $periodo->nombre()]);

        return back()->with('estado', "Período {$periodo->nombre()} cerrado.");
    }

    public function cuentas(): View
    {
        return view('admin.contabilidad.cuentas', [
            'cuentas' => CuentaContable::query()->withCount('movimientos')->orderBy('codigo')->get(),
            'agrupadoras' => CuentaContable::query()->where('acepta_movimientos', false)->orderBy('codigo')->get(),
        ]);
    }

    public function guardarCuenta(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'padre_id' => ['required', Rule::exists('cuentas_contables', 'id')->where('acepta_movimientos', false)],
            'codigo' => ['required', 'string', 'max:20', 'regex:/^\d+(\.\d+)*$/', 'unique:cuentas_contables,codigo'],
            'nombre' => ['required', 'string', 'max:120'],
        ]);

        $padre = CuentaContable::findOrFail($datos['padre_id']);

        if (! str_starts_with($datos['codigo'], $padre->codigo.'.')) {
            return back()->withInput()->withErrors(['codigo' => "El código debe empezar con {$padre->codigo}."]);
        }

        $cuenta = CuentaContable::create($datos + ['tipo' => $padre->tipo, 'naturaleza' => $padre->naturaleza, 'acepta_movimientos' => true]);
        $this->bitacora->registrar('cuenta_creada', $cuenta, null, $cuenta->only(['codigo', 'nombre']));

        return back()->with('estado', "Cuenta {$cuenta->codigo} creada.");
    }
}
