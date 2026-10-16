<x-admin-layout :title="'Kardex · '.$producto->sku">
    <a href="{{ route('admin.inventario.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a inventario</a>

    <div class="mt-4 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">{{ $producto->nombre }}</h2>
                <p class="text-xs text-slate-500">{{ $producto->marca->nombre }} · {{ $producto->categoria->nombre }} · Los movimientos no se pueden modificar ni borrar.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <tr><th class="px-4 py-2">Fecha</th><th class="px-4 py-2">Movimiento</th><th class="px-4 py-2 text-right">Entrada</th><th class="px-4 py-2 text-right">Salida</th><th class="px-4 py-2 text-right">Saldo</th><th class="px-4 py-2 text-right">Costo unit.</th><th class="px-4 py-2 text-right">Costo prom.</th><th class="px-4 py-2">Detalle</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($movimientos as $movimiento)
                            <tr>
                                <td class="px-4 py-2 text-xs whitespace-nowrap text-slate-500">{{ $movimiento->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">{{ $movimiento->tipo->etiqueta() }}</td>
                                <td class="px-4 py-2 text-right text-emerald-700">{{ $movimiento->tipo->esEntrada() ? $movimiento->cantidad : '' }}</td>
                                <td class="px-4 py-2 text-right text-acento-700">{{ $movimiento->tipo->esEntrada() ? '' : $movimiento->cantidad }}</td>
                                <td class="px-4 py-2 text-right font-semibold">{{ $movimiento->existencia_nueva }}</td>
                                <td class="px-4 py-2 text-right">Q{{ number_format($movimiento->costo_unitario, 2) }}</td>
                                <td class="px-4 py-2 text-right">Q{{ number_format($movimiento->costo_promedio_nuevo, 2) }}</td>
                                <td class="px-4 py-2 text-xs text-slate-500">
                                    {{ $movimiento->motivo }}
                                    @if ($movimiento->referencia_tipo === 'Compra')
                                        <a href="{{ route('admin.compras.show', $movimiento->referencia_id) }}" class="text-marca-600 hover:underline">Compra #{{ $movimiento->referencia_id }}</a>
                                    @elseif ($movimiento->referencia_tipo)
                                        {{ $movimiento->referencia_tipo }} #{{ $movimiento->referencia_id }}
                                    @endif
                                    <span class="block">{{ $movimiento->usuario?->nombre }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Sin movimientos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-5 py-3">{{ $movimientos->links() }}</div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <dl class="grid grid-cols-2 gap-y-1.5">
                    <dt class="text-slate-500">Existencia</dt><dd class="text-right font-bold">{{ $producto->existencia }}</dd>
                    <dt class="text-slate-500">Reservado</dt><dd class="text-right">{{ $producto->reservado }}</dd>
                    <dt class="text-slate-500">Disponible</dt><dd class="text-right">{{ $producto->disponible() }}</dd>
                    <dt class="text-slate-500">Costo promedio</dt><dd class="text-right">Q{{ number_format($producto->costo_promedio, 4) }}</dd>
                    <dt class="text-slate-500">Valor</dt><dd class="text-right">Q{{ number_format($producto->existencia * $producto->costo_promedio, 2) }}</dd>
                </dl>
            </section>

            @if (Auth::user()->tieneRolEfectivo(\App\Enums\Rol::Admin, \App\Enums\Rol::Vendedor))
                <form method="POST" action="{{ route('admin.inventario.ajustar', $producto) }}" class="space-y-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    @csrf
                    <h2 class="font-semibold text-slate-900">Ajuste de inventario</h2>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="tipo" value="Tipo" />
                            <select id="tipo" name="tipo" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                                <option value="merma" @selected(old('tipo') === 'merma')>Merma (−)</option>
                                <option value="sobrante" @selected(old('tipo') === 'sobrante')>Sobrante (+)</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="cantidad" value="Cantidad" />
                            <x-text-input id="cantidad" name="cantidad" type="number" min="1" class="mt-1 block w-full" :value="old('cantidad')" required />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="motivo" value="Motivo (obligatorio)" />
                        <x-text-input id="motivo" name="motivo" class="mt-1 block w-full" :value="old('motivo')" placeholder="Ej. producto dañado en bodega" required />
                    </div>
                    <x-input-error :messages="$errors->all()" />
                    <x-primary-button class="w-full">Registrar ajuste</x-primary-button>
                </form>
            @endif
        </aside>
    </div>
</x-admin-layout>
