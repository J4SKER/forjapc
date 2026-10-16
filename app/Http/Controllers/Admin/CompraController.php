<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EstadoCompra;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CompraRequest;
use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\CompraService;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Compras a proveedores: borrador → recibida (entra al kardex) o anulada.
 */
class CompraController extends Controller
{
    public function __construct(private CompraService $compras) {}

    public function index(Request $request): View
    {
        return view('admin.compras.index', [
            'compras' => Compra::query()->with('proveedor:id,nombre')
                ->when($request->filled('estado'), fn (Builder $q) => $q->where('estado', $request->string('estado')->value()))
                ->when($request->filled('proveedor'), fn (Builder $q) => $q->where('proveedor_id', $request->integer('proveedor')))
                ->latest('id')->paginate(20)->withQueryString(),
            'proveedores' => Proveedor::query()->orderBy('nombre')->get(['id', 'nombre']),
            'estados' => EstadoCompra::cases(),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Compra(['fecha_factura' => today()]));
    }

    public function store(CompraRequest $request): RedirectResponse
    {
        $compra = $this->compras->guardarBorrador($request->safe()->except('lineas'), $request->validated('lineas'));

        return redirect()->route('admin.compras.show', $compra)->with('estado', 'Compra guardada como borrador. Recíbela cuando llegue la mercadería.');
    }

    public function show(Compra $compra): View
    {
        return view('admin.compras.show', ['compra' => $compra->load(['proveedor', 'detalle.producto', 'usuario', 'receptor'])]);
    }

    public function edit(Compra $compra): View|RedirectResponse
    {
        if (! $compra->esBorrador()) {
            return redirect()->route('admin.compras.show', $compra)->with('estado', 'Solo se pueden editar compras en borrador.');
        }

        return $this->formulario($compra->load('detalle'));
    }

    public function update(CompraRequest $request, Compra $compra): RedirectResponse
    {
        try {
            $this->compras->guardarBorrador($request->safe()->except('lineas'), $request->validated('lineas'), $compra);
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        return redirect()->route('admin.compras.show', $compra)->with('estado', 'Compra actualizada.');
    }

    public function recibir(Compra $compra): RedirectResponse
    {
        try {
            $this->compras->recibir($compra);
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        return back()->with('estado', 'Mercadería recibida: las existencias y el costo promedio se actualizaron en el kardex.');
    }

    public function anular(Compra $compra): RedirectResponse
    {
        try {
            $this->compras->anular($compra);
        } catch (DomainException $e) {
            return back()->with('estado', $e->getMessage());
        }

        return back()->with('estado', 'Compra anulada.');
    }

    private function formulario(Compra $compra): View
    {
        return view('admin.compras.form', [
            'compra' => $compra,
            'proveedores' => Proveedor::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'nit']),
            'productos' => Producto::query()->with('categoria:id,nombre')->orderBy('nombre')
                ->get(['id', 'nombre', 'sku', 'categoria_id', 'costo_promedio'])
                ->map(fn (Producto $p) => ['id' => $p->id, 'texto' => "{$p->sku} · {$p->nombre}", 'costo' => (float) $p->costo_promedio])
                ->values(),
        ]);
    }
}
