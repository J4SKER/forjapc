<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoOrdenArmado;
use App\Http\Controllers\Controller;
use App\Models\OrdenArmado;
use App\Services\TallerService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Taller de armado: órdenes, lista de verificación y pruebas de estrés.
 */
class TallerController extends Controller
{
    public function __construct(private TallerService $taller) {}

    public function index(Request $request): View
    {
        $estado = EstadoOrdenArmado::tryFrom((string) $request->input('estado'));

        return view('admin.taller.index', [
            'ordenes' => OrdenArmado::query()->with(['pedido:id,numero,usuario_id', 'pedido.usuario:id,nombre', 'tecnico:id,nombre'])
                ->when($estado, fn (Builder $q) => $q->where('estado', $estado), fn (Builder $q) => $q->where('estado', '!=', EstadoOrdenArmado::Completada))
                ->orderByRaw("FIELD(estado, 'INCIDENCIA', 'EN_PRUEBAS', 'EN_ARMADO', 'PENDIENTE', 'COMPLETADA')")
                ->oldest('id')->paginate(20)->withQueryString(),
            'estado' => $estado,
            'estados' => EstadoOrdenArmado::cases(),
        ]);
    }

    public function show(OrdenArmado $orden): View
    {
        return view('admin.taller.show', ['orden' => $orden->load(['pedido.usuario', 'tecnico', 'piezas.producto.categoria'])]);
    }

    public function tomar(Request $request, OrdenArmado $orden): RedirectResponse
    {
        return $this->ejecutar(fn () => $this->taller->tomar($orden, $request->user()), 'Tomaste la orden de armado.');
    }

    public function checklist(Request $request, OrdenArmado $orden): RedirectResponse
    {
        $datos = $request->validate([
            'checklist' => ['array'],
            'checklist.*' => ['boolean'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->ejecutar(fn () => $this->taller->actualizarChecklist($orden, $datos['checklist'] ?? [], $datos['notas'] ?? null), 'Lista de verificación guardada.');
    }

    public function pruebas(Request $request, OrdenArmado $orden): RedirectResponse
    {
        $datos = $request->validate([
            'temp_cpu_max' => ['required', 'integer', 'min:20', 'max:110'],
            'temp_gpu_max' => ['nullable', 'integer', 'min:20', 'max:110'],
            'minutos_estres' => ['required', 'integer', 'min:5', 'max:600'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ], [], ['temp_cpu_max' => 'temperatura máxima del CPU', 'temp_gpu_max' => 'temperatura máxima de la GPU', 'minutos_estres' => 'minutos de prueba de estrés']);

        $datos['aprobada'] = $request->boolean('aprobada');

        return $this->ejecutar(fn () => $this->taller->registrarPruebas($orden, $datos), 'Resultados de pruebas registrados.');
    }

    public function completar(OrdenArmado $orden): RedirectResponse
    {
        return $this->ejecutar(fn () => $this->taller->completar($orden), 'Orden completada. Si era la última del pedido, ya está listo para entrega.');
    }

    public function incidencia(Request $request, OrdenArmado $orden): RedirectResponse
    {
        $datos = $request->validate(['descripcion' => ['required', 'string', 'min:5', 'max:500']]);

        return $this->ejecutar(fn () => $this->taller->reportarIncidencia($orden, $datos['descripcion']), 'Incidencia registrada.');
    }

    private function ejecutar(callable $accion, string $exito): RedirectResponse
    {
        try {
            $accion();
        } catch (DomainException $e) {
            return back()->withInput()->with('estado', $e->getMessage());
        }

        return back()->with('estado', $exito);
    }
}
