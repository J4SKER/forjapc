<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use App\Services\BitacoraService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function __construct(private BitacoraService $bitacora) {}

    public function index(Request $request): View
    {
        return view('admin.proveedores.index', [
            'proveedores' => Proveedor::query()->withCount('compras')
                ->when($request->filled('q'), fn (Builder $q) => $q->where(fn (Builder $q) => $q
                    ->where('nombre', 'like', '%'.$request->string('q')->value().'%')
                    ->orWhere('nit', 'like', '%'.$request->string('q')->value().'%')))
                ->orderBy('nombre')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.proveedores.form', ['proveedor' => new Proveedor(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $proveedor = Proveedor::create($this->validar($request));
        $this->bitacora->registrar('proveedor_creado', $proveedor, null, $proveedor->only(['nombre', 'nit']));

        return redirect()->route('admin.proveedores.index')->with('estado', "Proveedor «{$proveedor->nombre}» creado.");
    }

    public function edit(Proveedor $proveedor): View
    {
        return view('admin.proveedores.form', ['proveedor' => $proveedor]);
    }

    public function update(Request $request, Proveedor $proveedor): RedirectResponse
    {
        $proveedor->update($this->validar($request, $proveedor));

        return redirect()->route('admin.proveedores.index')->with('estado', "Proveedor «{$proveedor->nombre}» actualizado.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Proveedor $proveedor = null): array
    {
        $request->merge(['nit' => strtoupper(trim((string) $request->input('nit')))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'nit' => ['required', 'string', 'max:15', 'regex:/^[0-9]{1,12}-?[0-9K]$/', Rule::unique('proveedores', 'nit')->ignore($proveedor)],
            'contacto' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'activo' => ['boolean'],
        ]);
        $datos['activo'] = $request->boolean('activo', true);

        return $datos;
    }
}
