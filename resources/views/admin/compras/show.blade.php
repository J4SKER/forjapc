<x-admin-layout :title="'Compra #'.$compra->id">
    <a href="{{ route('admin.compras.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a compras</a>

    <div class="mt-4 grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-900">{{ $compra->proveedor->nombre }}</h2>
                    <p class="text-xs text-slate-500">NIT {{ $compra->proveedor->nit }} · Factura {{ $compra->factura() }} · {{ $compra->fecha_factura->format('d/m/Y') }}</p>
                </div>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $compra->estado->color() }}">{{ $compra->estado->etiqueta() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <tr><th class="px-5 py-2">Producto</th><th class="px-3 py-2 text-right">Cantidad</th><th class="px-3 py-2 text-right">Costo unit.</th><th class="px-5 py-2 text-right">Subtotal</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($compra->detalle as $linea)
                            <tr>
                                <td class="px-5 py-2"><a href="{{ route('admin.inventario.kardex', $linea->producto) }}" class="hover:text-marca-600">{{ $linea->producto->nombre }}</a> <span class="text-xs text-slate-400">{{ $linea->producto->sku }}</span></td>
                                <td class="px-3 py-2 text-right">{{ $linea->cantidad }}</td>
                                <td class="px-3 py-2 text-right">Q{{ number_format($linea->costo_unitario, 2) }}</td>
                                <td class="px-5 py-2 text-right">Q{{ number_format($linea->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-slate-200">
                        <tr><td colspan="3" class="px-5 py-1.5 text-right text-slate-500">Subtotal</td><td class="px-5 py-1.5 text-right">Q{{ number_format($compra->subtotal, 2) }}</td></tr>
                        <tr><td colspan="3" class="px-5 py-1.5 text-right text-slate-500">IVA crédito fiscal</td><td class="px-5 py-1.5 text-right">Q{{ number_format($compra->iva, 2) }}</td></tr>
                        <tr><td colspan="3" class="px-5 py-2 text-right font-semibold">Total</td><td class="px-5 py-2 text-right font-bold">Q{{ number_format($compra->total, 2) }}</td></tr>
                    </tfoot>
                </table>
            </div>
            @if ($compra->notas)
                <p class="border-t border-slate-100 px-5 py-3 text-sm text-slate-600">{{ $compra->notas }}</p>
            @endif
        </section>

        <aside class="space-y-4">
            <section class="rounded-xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <dl class="space-y-1.5">
                    <div class="flex justify-between"><dt class="text-slate-500">Registrada por</dt><dd>{{ $compra->usuario?->nombre ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Creada</dt><dd>{{ $compra->created_at->format('d/m/Y H:i') }}</dd></div>
                    @if ($compra->recibida_at)
                        <div class="flex justify-between"><dt class="text-slate-500">Recibida por</dt><dd>{{ $compra->receptor?->nombre ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Recibida</dt><dd>{{ $compra->recibida_at->format('d/m/Y H:i') }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($compra->esBorrador())
                <section class="space-y-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <form method="POST" action="{{ route('admin.compras.recibir', $compra) }}" onsubmit="return confirm('¿Confirmas que la mercadería llegó completa? Las existencias se actualizarán.')">
                        @csrf
                        <x-primary-button class="w-full">Recibir mercadería</x-primary-button>
                    </form>
                    <a href="{{ route('admin.compras.edit', $compra) }}" class="block rounded-lg border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Editar borrador</a>
                    <form method="POST" action="{{ route('admin.compras.anular', $compra) }}" onsubmit="return confirm('¿Anular esta compra?')">
                        @csrf
                        <button type="submit" class="w-full rounded-lg px-4 py-2 text-sm font-semibold text-acento-700 hover:bg-red-50">Anular compra</button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</x-admin-layout>
