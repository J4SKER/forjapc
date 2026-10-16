<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ParametroSistema;
use App\Models\TipoCambio;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Parámetros del negocio y tipo de cambio, editables sin tocar el código.
 */
class ParametroController extends Controller
{
    public function __construct(private BitacoraService $bitacora) {}

    public function index(): View
    {
        return view('admin.parametros.index', [
            'parametros' => ParametroSistema::query()->orderBy('clave')->get(),
            'tiposCambio' => TipoCambio::query()->latest('fecha')->limit(10)->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $parametros = ParametroSistema::query()->get()->keyBy('clave');

        $reglas = $parametros->mapWithKeys(fn (ParametroSistema $parametro) => [
            'valores.'.$parametro->clave => match ($parametro->tipo) {
                'entero' => ['required', 'integer', 'min:0'],
                'decimal' => ['required', 'numeric', 'min:0'],
                'booleano' => ['nullable', 'boolean'],
                default => ['nullable', 'string', 'max:500'],
            },
        ])->all();

        $validados = $request->validate($reglas, [], $parametros->mapWithKeys(
            fn (ParametroSistema $parametro) => ['valores.'.$parametro->clave => $parametro->descripcion ?? $parametro->clave],
        )->all());

        DB::transaction(function () use ($parametros, $validados) {
            foreach ($parametros as $clave => $parametro) {
                $nuevo = (string) ($validados['valores'][$clave] ?? '');

                if ($parametro->tipo === 'booleano') {
                    $nuevo = filter_var($nuevo, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
                }

                if ($nuevo === (string) $parametro->valor) {
                    continue;
                }

                $this->bitacora->registrar('parametro_actualizado', $parametro, ['valor' => $parametro->valor], ['valor' => $nuevo]);
                $parametro->update(['valor' => $nuevo]);
            }
        });

        return back()->with('estado', 'Parámetros guardados.');
    }

    public function guardarTipoCambio(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'tasa' => ['required', 'numeric', 'min:1', 'max:20'],
        ]);

        $tipoCambio = TipoCambio::updateOrCreate(['fecha' => $datos['fecha']], ['tasa' => $datos['tasa'], 'fuente' => 'MANUAL']);

        $this->bitacora->registrar('tipo_cambio_registrado', $tipoCambio, null, $datos);

        return back()->with('estado', 'Tipo de cambio registrado.');
    }
}
