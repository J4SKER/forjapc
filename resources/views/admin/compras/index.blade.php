<x-admin-layout title="Compras">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <form method="GET" class="flex flex-wrap gap-2">
            <select name="proveedor" aria-label="Proveedor" class="rounded-lg border-slate-300 text-sm">
                <option value="">Todos los proveedores</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected((int) request('proveedor') === $proveedor->id)>{{ $proveedor->nombre }}</option>
                @endforeach
            </select>
            <select name="estado" aria-label="Estado" class="rounded-lg border-slate-300 text-sm">
                <option value="">Todos los estados</option>
                @foreach ($estados as $estado)
                    <option value="{{ $estado->value }}" @selected(request('estado') === $estado->value)>{{ $estado->etiqueta() }}</option>
                @endforeach
            </select>
            <x-secondary-button type="submit">Filtrar</x-secondary-button>
        </form>
        <a href="{{ route('admin.compras.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-acento-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-acento-700">Registrar compra</a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="px-4 py-3">#</th><th class="px-4 py-3">Proveedor</th><th class="px-4 py-3">Factura</th><th class="px-4 py-3">Fecha</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($compras as $compra)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 text-slate-500">{{ $compra->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $compra->proveedor->nombre }}</td>
                        <td class="px-4 py-3">{{ $compra->factura() }}</td>
                        <td class="px-4 py-3">{{ $compra->fecha_factura->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right font-medium">Q{{ number_format($compra->total, 2) }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $compra->estado->color() }}">{{ $compra->estado->etiqueta() }}</span></td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.compras.show', $compra) }}" class="text-xs font-semibold text-marca-600 hover:underline">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Sin compras registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $compras->links() }}</div>
</x-admin-layout>
