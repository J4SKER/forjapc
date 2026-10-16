<x-admin-layout title="Pedidos">
    <div class="mb-4 flex flex-wrap gap-2 text-xs">
        <a href="{{ route('admin.pedidos.index') }}" @class(['rounded-full px-3 py-1.5 font-semibold', 'bg-marca-700 text-white' => ! request('estado'), 'bg-white text-slate-600 ring-1 ring-slate-200' => request('estado')])>Todos</a>
        @foreach ($estados as $estado)
            <a href="{{ route('admin.pedidos.index', ['estado' => $estado->value]) }}" @class([
                'rounded-full px-3 py-1.5 font-semibold',
                'bg-marca-700 text-white' => request('estado') === $estado->value,
                'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => request('estado') !== $estado->value,
            ])>{{ $estado->etiqueta() }} ({{ $conteos[$estado->value] ?? 0 }})</a>
        @endforeach
    </div>

    <form method="GET" class="mb-4 grid gap-3 sm:grid-cols-4">
        <input type="hidden" name="estado" value="{{ request('estado') }}">
        <div class="sm:col-span-2">
            <label for="q" class="text-xs font-medium text-slate-600">Buscar</label>
            <x-text-input id="q" name="q" :value="request('q')" placeholder="Número, cliente o correo" class="mt-1 block w-full" />
        </div>
        <div>
            <label for="desde" class="text-xs font-medium text-slate-600">Desde</label>
            <x-text-input id="desde" name="desde" type="date" :value="request('desde')" class="mt-1 block w-full" />
        </div>
        <div class="flex items-end gap-2">
            <div class="flex-1">
                <label for="hasta" class="text-xs font-medium text-slate-600">Hasta</label>
                <x-text-input id="hasta" name="hasta" type="date" :value="request('hasta')" class="mt-1 block w-full" />
            </div>
            <x-secondary-button type="submit">Filtrar</x-secondary-button>
        </div>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="px-4 py-3">Pedido</th><th class="px-4 py-3">Cliente</th><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Pago</th><th class="px-4 py-3">Entrega</th><th class="px-4 py-3 text-right">Total</th><th class="px-4 py-3">Estado</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pedidos as $pedido)
                    <tr class="cursor-pointer hover:bg-slate-50" onclick="window.location='{{ route('admin.pedidos.show', $pedido) }}'">
                        <td class="px-4 py-3 font-semibold text-marca-700"><a href="{{ route('admin.pedidos.show', $pedido) }}">{{ $pedido->numero }}</a></td>
                        <td class="px-4 py-3">{{ $pedido->usuario->nombre }}<span class="block text-xs text-slate-500">{{ $pedido->usuario->email }}</span></td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $pedido->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-xs">{{ $pedido->metodo_pago->etiqueta() }}</td>
                        <td class="px-4 py-3 text-xs">{{ $pedido->esEnvio() ? 'Envío' : 'Recoger' }}</td>
                        <td class="px-4 py-3 text-right font-semibold">Q{{ number_format($pedido->total, 2) }}</td>
                        <td class="px-4 py-3"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $pedido->estado->color() }}">{{ $pedido->estado->etiqueta() }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No hay pedidos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pedidos->links() }}</div>
</x-admin-layout>
