<x-admin-layout title="Productos">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" class="grid flex-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="q" class="text-xs font-medium text-slate-600">Buscar</label>
                <x-text-input id="q" name="q" :value="request('q')" placeholder="Nombre, SKU o marca" class="mt-1 block w-full" />
            </div>
            <div>
                <label for="categoria" class="text-xs font-medium text-slate-600">Categoría</label>
                <select id="categoria" name="categoria" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    <option value="">Todas</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}" @selected((int) request('categoria') === $categoria->id)>{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <select name="estado" aria-label="Estado" class="block w-full rounded-lg border-slate-300 text-sm shadow-sm">
                    <option value="">Todos</option>
                    <option value="stock_bajo" @selected(request('estado') === 'stock_bajo')>Stock bajo</option>
                    <option value="inactivos" @selected(request('estado') === 'inactivos')>Inactivos</option>
                </select>
                <x-secondary-button type="submit">Filtrar</x-secondary-button>
            </div>
        </form>
        @if (Auth::user()->tieneRolEfectivo(\App\Enums\Rol::Admin, \App\Enums\Rol::Vendedor))
            <a href="{{ route('admin.productos.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-acento-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-acento-700">
                <x-icono nombre="paquete" class="size-4" /> Nuevo producto
            </a>
        @endif
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3">Categoría</th>
                    <th class="px-4 py-3 text-right">Precio</th>
                    <th class="px-4 py-3 text-right">Existencia</th>
                    <th class="px-4 py-3 text-right">Reservado</th>
                    <th class="px-4 py-3 text-right">Costo prom.</th>
                    <th class="px-4 py-3">Estado</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($productos as $producto)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900">{{ $producto->nombre }}</p>
                            <p class="text-xs text-slate-500">{{ $producto->sku }} · {{ $producto->marca->nombre }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $producto->categoria->nombre }}</td>
                        <td class="px-4 py-3 text-right font-medium">Q{{ number_format($producto->precio, 2) }}</td>
                        <td @class(['px-4 py-3 text-right font-semibold', 'text-acento-700' => $producto->stockBajo()])>{{ $producto->existencia }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">{{ $producto->reservado }}</td>
                        <td class="px-4 py-3 text-right text-slate-500">Q{{ number_format($producto->costo_promedio, 2) }}</td>
                        <td class="px-4 py-3">
                            @if (! $producto->activo)
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">Inactivo</span>
                            @elseif ($producto->stockBajo())
                                <span class="rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-acento-700">Stock bajo</span>
                            @else
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Activo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.inventario.kardex', $producto) }}" class="text-xs font-semibold text-slate-600 hover:underline">Kardex</a>
                            <a href="{{ route('admin.productos.edit', $producto) }}" class="ml-3 text-xs font-semibold text-marca-600 hover:underline">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">No hay productos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $productos->links() }}</div>
</x-admin-layout>
