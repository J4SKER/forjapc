<x-admin-layout title="Reporte de ventas">
    <x-filtro-reporte :desde="$desde" :hasta="$hasta" />

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Ventas cobradas', 'Q'.number_format($resumen['total'], 2), $resumen['pedidos'].($resumen['pedidos'] === 1 ? ' pedido' : ' pedidos')],
            ['Base imponible', 'Q'.number_format($resumen['base_imponible'], 2), 'IVA Q'.number_format($resumen['iva'], 2)],
            ['Ticket promedio', 'Q'.number_format($resumen['pedidos'] ? $resumen['total'] / $resumen['pedidos'] : 0, 2), 'por pedido'],
            ['Por método de pago', 'PayPal Q'.number_format($resumen['paypal'], 2), 'Transferencia Q'.number_format($resumen['transferencia'], 2)],
        ] as [$titulo, $valor, $detalle])
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">{{ $titulo }}</p>
                <p class="text-xl font-extrabold text-slate-900">{{ $valor }}</p>
                <p class="text-xs text-slate-500">{{ $detalle }}</p>
            </div>
        @endforeach
    </div>

    @php($maximo = max(1, $por_dia->max('total')))
    <section class="mt-6 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <h2 class="font-semibold text-slate-900">Ventas por día</h2>
        <div class="mt-4 flex h-48 items-end gap-1 overflow-x-auto" role="img" aria-label="Gráfica de ventas por día">
            @foreach ($por_dia as $dia)
                <div class="group flex min-w-4 flex-1 flex-col items-center justify-end" title="{{ \Carbon\Carbon::parse($dia['fecha'])->format('d/m') }}: Q{{ number_format($dia['total'], 2) }} ({{ $dia['pedidos'] }} pedidos)">
                    <div class="w-full rounded-t bg-marca-500 transition group-hover:bg-acento-500" data-alto="{{ max(2, $dia['total'] / $maximo * 100) }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-1 flex justify-between text-xs text-slate-400"><span>{{ $desde->format('d/m') }}</span><span>{{ $hasta->format('d/m') }}</span></div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <h2 class="border-b border-slate-100 px-5 py-3 font-semibold text-slate-900">Por categoría</h2>
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-slate-500 uppercase"><tr><th class="px-5 py-2">Categoría</th><th class="px-3 py-2 text-right">Unidades</th><th class="px-3 py-2 text-right">Ventas</th><th class="px-5 py-2 text-right">Margen</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($por_categoria as $fila)
                        @php($base = $fila->total / 1.12)
                        <tr><td class="px-5 py-2">{{ $fila->categoria }}</td><td class="px-3 py-2 text-right">{{ $fila->unidades }}</td><td class="px-3 py-2 text-right">Q{{ number_format($fila->total, 2) }}</td>
                            <td class="px-5 py-2 text-right">{{ $base > 0 ? round(($base - $fila->costo) / $base * 100).' %' : '—' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-500">Sin ventas en el período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <h2 class="border-b border-slate-100 px-5 py-3 font-semibold text-slate-900">Productos más vendidos</h2>
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs text-slate-500 uppercase"><tr><th class="px-5 py-2">Producto</th><th class="px-3 py-2 text-right">Unidades</th><th class="px-5 py-2 text-right">Ventas</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($mas_vendidos as $fila)
                        <tr><td class="px-5 py-2">{{ $fila->nombre }} <span class="text-xs text-slate-400">{{ $fila->sku }}</span></td><td class="px-3 py-2 text-right">{{ $fila->unidades }}</td><td class="px-5 py-2 text-right">Q{{ number_format($fila->total, 2) }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-6 text-center text-slate-500">Sin ventas en el período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
</x-admin-layout>
