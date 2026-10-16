<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MarcaController extends Controller
{
    public function index(): View
    {
        return view('admin.marcas.index', [
            'marcas' => Marca::query()->withCount('productos')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $marca = Marca::create($this->validar($request));

        return back()->with('estado', "Marca «{$marca->nombre}» creada.");
    }

    public function update(Request $request, Marca $marca): RedirectResponse
    {
        $marca->update($this->validar($request, $marca));

        return back()->with('estado', "Marca «{$marca->nombre}» actualizada.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Marca $marca = null): array
    {
        $request->merge(['slug' => Str::slug((string) $request->input('nombre'))]);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:60', Rule::unique('marcas', 'nombre')->ignore($marca)],
            'slug' => ['required', Rule::unique('marcas', 'slug')->ignore($marca)],
            'activo' => ['boolean'],
        ]);
        $datos['activo'] = $request->boolean('activo', true);

        return $datos;
    }
}
