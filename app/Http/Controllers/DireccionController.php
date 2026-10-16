<?php

namespace App\Http\Controllers;

use App\Http\Requests\DireccionRequest;
use App\Models\Departamento;
use App\Models\Direccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Libreta de direcciones de entrega del cliente.
 */
class DireccionController extends Controller
{
    private const MAXIMO_DIRECCIONES = 10;

    public function index(Request $request): View
    {
        return view('cuenta.direcciones.index', [
            'direcciones' => $request->user()->direcciones()->with('departamento')
                ->orderByDesc('es_principal')->orderBy('alias')->get(),
            'maximo' => self::MAXIMO_DIRECCIONES,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->direcciones()->count() >= self::MAXIMO_DIRECCIONES) {
            return redirect()->route('direcciones.index')->with('estado', 'Llegaste al máximo de '.self::MAXIMO_DIRECCIONES.' direcciones.');
        }

        return view('cuenta.direcciones.form', [
            'direccion' => new Direccion(['nombre_receptor' => $request->user()->nombre, 'telefono' => $request->user()->telefono]),
            'departamentos' => $this->departamentos(),
        ]);
    }

    public function store(DireccionRequest $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->direcciones()->count() >= self::MAXIMO_DIRECCIONES, 422, 'Llegaste al máximo de direcciones.');

        DB::transaction(function () use ($request, $user) {
            $esPrimera = ! $user->direcciones()->exists();
            $direccion = $user->direcciones()->make($request->validated());
            $direccion->es_principal = $esPrimera || $request->boolean('es_principal');
            $direccion->save();

            $this->dejarUnicaPrincipal($user, $direccion);
        });

        return redirect()->route('direcciones.index')->with('estado', 'Dirección guardada.');
    }

    public function edit(Direccion $direccion): View
    {
        Gate::authorize('update', $direccion);

        return view('cuenta.direcciones.form', [
            'direccion' => $direccion,
            'departamentos' => $this->departamentos(),
        ]);
    }

    public function update(DireccionRequest $request, Direccion $direccion): RedirectResponse
    {
        Gate::authorize('update', $direccion);

        DB::transaction(function () use ($request, $direccion) {
            $direccion->fill($request->validated());
            $direccion->es_principal = $direccion->es_principal || $request->boolean('es_principal');
            $direccion->save();

            $this->dejarUnicaPrincipal($request->user(), $direccion);
        });

        return redirect()->route('direcciones.index')->with('estado', 'Dirección actualizada.');
    }

    public function destroy(Request $request, Direccion $direccion): RedirectResponse
    {
        Gate::authorize('delete', $direccion);

        DB::transaction(function () use ($request, $direccion) {
            $eraPrincipal = $direccion->es_principal;
            $direccion->delete();

            if ($eraPrincipal) {
                $request->user()->direcciones()->oldest()->first()?->update(['es_principal' => true]);
            }
        });

        return redirect()->route('direcciones.index')->with('estado', 'Dirección eliminada.');
    }

    /**
     * Si la dirección quedó como principal, las demás del usuario dejan de serlo.
     */
    private function dejarUnicaPrincipal(User $user, Direccion $direccion): void
    {
        if ($direccion->es_principal) {
            $user->direcciones()->whereKeyNot($direccion->id)->update(['es_principal' => false]);
        }
    }

    /**
     * @return Collection<int, Departamento>
     */
    private function departamentos(): Collection
    {
        return Departamento::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'tarifa_envio']);
    }
}
