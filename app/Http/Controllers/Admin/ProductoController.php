<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoComponente;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductoRequest;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ProductoImagen;
use App\Models\Socket;
use App\Services\BitacoraService;
use App\Services\BuscadorProductos;
use App\Support\CamposEspecificacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Administración del catálogo: productos, especificaciones técnicas e imágenes.
 */
class ProductoController extends Controller
{
    public function __construct(private BitacoraService $bitacora) {}

    public function index(Request $request, BuscadorProductos $buscador): View
    {
        $productos = Producto::query()
            ->with(['categoria', 'marca'])
            ->when($request->filled('q'), fn (Builder $q) => $buscador->aplicar($q, (string) $request->string('q')))
            ->when($request->filled('categoria'), fn (Builder $q) => $q->where('categoria_id', $request->integer('categoria')))
            ->when($request->input('estado') === 'inactivos', fn (Builder $q) => $q->where('activo', false))
            ->when($request->input('estado') === 'stock_bajo', fn (Builder $q) => $q->whereRaw('existencia - reservado <= stock_minimo'))
            ->when(! $request->filled('q'), fn (Builder $q) => $q->orderBy('nombre'))
            ->paginate(20)
            ->withQueryString();

        return view('admin.productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::query()->orderBy('orden')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->formulario(new Producto(['activo' => true, 'garantia_meses' => 12, 'stock_minimo' => 2, 'consumo_watts' => 0]));
    }

    public function store(ProductoRequest $request): RedirectResponse
    {
        $producto = $this->guardar($request, new Producto);
        $this->bitacora->registrar('producto_creado', $producto, null, ['sku' => $producto->sku, 'precio' => $producto->precio]);

        return redirect()->route('admin.productos.edit', $producto)->with('estado', 'Producto creado. Su existencia se carga registrando una compra.');
    }

    public function edit(Producto $producto): View
    {
        return $this->formulario($producto->load(['imagenes']));
    }

    public function update(ProductoRequest $request, Producto $producto): RedirectResponse
    {
        $precioAnterior = (string) $producto->precio;
        $this->guardar($request, $producto);

        if ($precioAnterior !== (string) $producto->precio) {
            $this->bitacora->registrar('precio_cambiado', $producto, ['precio' => $precioAnterior], ['precio' => (string) $producto->precio]);
        }

        return redirect()->route('admin.productos.edit', $producto)->with('estado', 'Producto actualizado.');
    }

    public function destroy(Producto $producto): RedirectResponse
    {
        $producto->update(['activo' => false]);
        $producto->delete();
        $this->bitacora->registrar('producto_eliminado', $producto, null, ['sku' => $producto->sku]);
        Cache::forget(BuscadorProductos::CACHE_VOCABULARIO);

        return redirect()->route('admin.productos.index')->with('estado', "Producto {$producto->sku} eliminado del catálogo.");
    }

    public function eliminarImagen(Producto $producto, ProductoImagen $imagen): RedirectResponse
    {
        abort_unless($imagen->producto_id === $producto->id, 404);
        $imagen->delete();

        return back()->with('estado', 'Imagen eliminada.');
    }

    private function formulario(Producto $producto): View
    {
        $categorias = Categoria::query()->orderBy('orden')->get();

        return view('admin.productos.form', [
            'producto' => $producto,
            'categorias' => $categorias,
            'marcas' => Marca::query()->orderBy('nombre')->get(),
            'sockets' => Socket::query()->orderBy('nombre')->get(),
            'campos' => collect(TipoComponente::cases())->mapWithKeys(fn (TipoComponente $t) => [$t->value => CamposEspecificacion::de($t)]),
            'tiposPorCategoria' => $categorias->mapWithKeys(fn (Categoria $c) => [$c->id => $c->tipo_componente?->value]),
            'especificacion' => $producto->exists ? $this->especificacionActual($producto) : [],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function especificacionActual(Producto $producto): array
    {
        $especificacion = $producto->especificacion();

        if ($especificacion === null) {
            return [];
        }

        $datos = $especificacion->attributesToArray();
        if ($producto->tipoComponente() === TipoComponente::Disipador) {
            $datos['sockets'] = $especificacion->sockets()->pluck('sockets.id')->all();
        }

        return $datos;
    }

    private function guardar(ProductoRequest $request, Producto $producto): Producto
    {
        DB::transaction(function () use ($request, $producto) {
            $producto->fill($request->safe()->except(['imagenes', 'esp']));
            $producto->destacado = $request->boolean('destacado');
            $producto->activo = $request->boolean('activo');
            $producto->save();

            $this->guardarEspecificacion($producto->load('categoria'), $request->validated('esp', []));
            $this->guardarImagenes($producto, $request->file('imagenes', []));
        });

        Cache::forget(BuscadorProductos::CACHE_VOCABULARIO);

        return $producto;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function guardarEspecificacion(Producto $producto, array $datos): void
    {
        $tipo = $producto->tipoComponente();

        // Si cambió de categoría, se eliminan las especificaciones de otro tipo.
        foreach (TipoComponente::cases() as $otro) {
            if ($otro !== $tipo) {
                $producto->{$otro->relacion()}()->delete();
            }
        }

        if ($tipo === null) {
            return;
        }

        $sockets = $datos['sockets'] ?? null;
        unset($datos['sockets']);

        foreach (CamposEspecificacion::de($tipo) as $campo => $definicion) {
            if ($definicion['tipo'] === 'booleano') {
                $datos[$campo] = (bool) ($datos[$campo] ?? false);
            }
        }

        $especificacion = $producto->{$tipo->relacion()}()->updateOrCreate(['producto_id' => $producto->id], $datos);

        if ($sockets !== null) {
            $especificacion->sockets()->sync($sockets);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $archivos
     */
    private function guardarImagenes(Producto $producto, array $archivos): void
    {
        $orden = (int) $producto->imagenes()->max('orden');

        foreach ($archivos as $archivo) {
            $producto->imagenes()->create([
                'ruta' => $archivo->store("productos/{$producto->id}", 'public'),
                'orden' => ++$orden,
            ]);
        }
    }
}
