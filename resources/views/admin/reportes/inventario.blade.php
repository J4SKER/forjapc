<x-admin-layout title="Valorización del inventario">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Valor del inventario al costo promedio ponderado, al día de hoy.</p>
        <a href="{{ request()->fullUrlWithQuery(['formato' => 'csv']) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><x-icono nombre="lista" class="size-4" /> Exportar CSV</a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($porCategoria as $categoria => $datos)
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">{{ $categoria }}</p>
                <p class="text-lg font-extrabold">Q{{ number_format($datos['valor'], 2) }}</p>
                <p class="text-xs text-slate-500">{{ $datos['unidades'] }} unidades</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="px-4 py-3">Producto</th><th class="px-4 py-3">Categoría</th><th class="px-4 py-3 text-right">Existencia</th><th class="px-4 py-3 text-right">Costo prom.</th><th class="px-4 py-3 text-right">Valor</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($existencias as $fila)
                    <tr @class(['bg-red-50/50' => $fila->stock_bajo])>
                        <td class="px-4 py-2">{{ $fila->nombre }} <span class="text-xs text-slate-400">{{ $fila->sku }}</span></td>
                        <td class="px-4 py-2 text-slate-600">{{ $fila->categoria }}</td>
                        <td class="px-4 py-2 text-right">{{ $fila->existencia }}</td>
                        <td class="px-4 py-2 text-right">Q{{ number_format($fila->costo_promedio, 2) }}</td>
                        <td class="px-4 py-2 text-right font-medium">Q{{ number_format($fila->valor, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="border-t-2 border-slate-200 font-bold">
                <tr><td colspan="2" class="px-4 py-2">Total</td><td class="px-4 py-2 text-right">{{ $existencias->sum('existencia') }}</td><td></td><td class="px-4 py-2 text-right">Q{{ number_format($existencias->sum('valor'), 2) }}</td></tr>
            </tfoot>
        </table>
    </div>
</x-admin-layout>
