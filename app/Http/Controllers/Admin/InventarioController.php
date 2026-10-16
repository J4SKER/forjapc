<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InventarioInsuficienteException;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Services\CompraService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Existencias, valorización, kardex por producto y ajustes de inventario.
 */
class InventarioController extends Controller
{
    public function index(Request $request): View
    {
        $consulta = Producto::query()->with(['categoria:id,nombre', 'marca:id,nombre'])
            ->when($request->filled('categoria'), fn (Builder $q) => $q->where('categoria_id', $request->integer('categoria')))
            ->when($request->boolean('stock_bajo'), fn (Builder $q) => $q->whereRaw('existencia - reservado <= stock_minimo'));

        return view('admin.inventario.index', [
            'productos' => (clone $consulta)->orderBy('nombre')->paginate(25)->withQueryString(),
            'categorias' => Categoria::query()->orderBy('orden')->get(['id', 'nombre']),
            'totales' => (clone $consulta)->reorder()->selectRaw('SUM(existencia) as unidades, SUM(existencia * costo_promedio) as valor, SUM(CASE WHEN existencia - reservado <= stock_minimo THEN 1 ELSE 0 END) as bajos')->first(),
        ]);
    }

    public function kardex(Producto $producto): View
    {
        return view('admin.inventario.kardex', [
            'producto' => $producto->load(['categoria', 'marca']),
            'movimientos' => $producto->movimientos()->with('usuario:id,nombre')->latest('id')->paginate(30),
            'resumen' => $producto->movimientos()->selectRaw('tipo, SUM(cantidad) as cantidad')->groupBy('tipo')->pluck('cantidad', 'tipo'),
        ]);
    }

    public function ajustar(Request $request, Producto $producto, CompraService $compras): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:sobrante,merma'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'motivo' => ['required', 'string', 'min:5', 'max:255'],
        ], [], ['motivo' => 'motivo del ajuste']);

        $diferencia = $datos['tipo'] === 'merma' ? -$datos['cantidad'] : $datos['cantidad'];

        try {
            $compras->ajustarInventario($producto, $diferencia, $datos['motivo']);
        } catch (InventarioInsuficienteException $e) {
            return back()->withErrors(['cantidad' => $e->getMessage()])->withInput();
        }

        return back()->with('estado', 'Ajuste registrado en el kardex.');
    }
}
