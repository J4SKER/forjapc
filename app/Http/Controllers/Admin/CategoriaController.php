<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TipoComponente;
use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    private const ICONOS = ['cpu', 'placa', 'ram', 'gpu', 'disco', 'fuente', 'gabinete', 'ventilador', 'monitor', 'teclado', 'mouse', 'audifonos', 'laptop', 'paquete'];

    public function __construct(private BitacoraService $bitacora) {}

    public function index(): View
    {
        return view('admin.categorias.index', [
            'categorias' => Categoria::query()->withCount('productos')->orderBy('orden')->get(),
            'tipos' => TipoComponente::cases(),
            'iconos' => self::ICONOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $categoria = Categoria::create($this->validar($request));
        $this->bitacora->registrar('categoria_creada', $categoria, null, $categoria->only(['nombre', 'slug']));

        return back()->with('estado', "Categoría «{$categoria->nombre}» creada.");
    }

    public function update(Request $request, Categoria $categoria): RedirectResponse
    {
        $categoria->update($this->validar($request, $categoria));

        return back()->with('estado', "Categoría «{$categoria->nombre}» actualizada.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Categoria $categoria = null): array
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('nombre'))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:90', Rule::unique('categorias', 'slug')->ignore($categoria)],
            'icono' => ['required', Rule::in(self::ICONOS)],
            'tipo_componente' => ['nullable', Rule::enum(TipoComponente::class), Rule::unique('categorias', 'tipo_componente')->ignore($categoria)],
            'orden' => ['required', 'integer', 'min:0', 'max:999'],
            'activo' => ['boolean'],
        ], [], ['tipo_componente' => 'tipo de componente']);

        $datos['activo'] = $request->boolean('activo');
        $datos['tipo_componente'] = $datos['tipo_componente'] ?? null;

        return $datos;
    }
}
