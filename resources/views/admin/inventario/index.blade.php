<x-admin-layout title="Inventario">
    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Unidades en bodega</p>
            <p class="text-2xl font-extrabold">{{ number_format($totales->unidades ?? 0) }}</p>
        </div>
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <p class="text-sm text-slate-500">Valor del inventario (al costo)</p>
            <p class="text-2xl font-extrabold">Q{{ number_format($totales->valor ?? 0, 2) }}</p>
        </div>
        <a href="{{ route('admin.inventario.index', ['stock_bajo' => 1]) }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 hover:ring-acento-500">
            <p class="text-sm text-slate-500">Productos con stock bajo</p>
            <p class="text-2xl font-extrabold text-acento-700">{{ (int) ($totales->bajos ?? 0) }}</p>
        </a>
    </div>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <select name="categoria" aria-label="Categoría" class="rounded-lg border-slate-300 text-sm">
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected((int) request('categoria') === $categoria->id)>{{ $categoria->nombre }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 rounded-lg bg-white px-3 text-sm ring-1 ring-slate-300">
            <input type="checkbox" name="stock_bajo" value="1" @checked(request()->boolean('stock_bajo')) class="rounded border-slate-300"> Solo stock bajo
        </label>
        <x-secondary-button type="submit">Filtrar</x-secondary-button>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="px-4 py-3">Producto</th><th class="px-4 py-3 text-right">Existencia</th><th class="px-4 py-3 text-right">Reservado</th><th class="px-4 py-3 text-right">Disponible</th><th class="px-4 py-3 text-right">Mínimo</th><th class="px-4 py-3 text-right">Costo prom.</th><th class="px-4 py-3 text-right">Valor</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($productos as $producto)
                    <tr @class(['hover:bg-slate-50', 'bg-red-50/50' => $producto->stockBajo()])>
                        <td class="px-4 py-3"><p class="font-medium">{{ $producto->nombre }}</p><p class="text-xs text-slate-500">{{ $producto->sku }} · {{ $producto->categoria->nombre }}</p></td>
                        <td class="px-4 py-3 text-right font-semibold">{{ $producto->existencia }}</td>
                        <td class="px-4 py-3 text-right">{{ $producto->reservado }}</td>
                        <td @class(['px-4 py-3 text-right font-semibold', 'text-acento-700' => $producto->stockBajo()])>{{ $producto->disponible() }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ $producto->stock_minimo }}</td>
                        <td class="px-4 py-3 text-right">Q{{ number_format($producto->costo_promedio, 2) }}</td>
                        <td class="px-4 py-3 text-right">Q{{ number_format($producto->existencia * $producto->costo_promedio, 2) }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.inventario.kardex', $producto) }}" class="text-xs font-semibold text-marca-600 hover:underline">Kardex</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $productos->links() }}</div>
</x-admin-layout>
