<x-admin-layout :title="$compra->exists ? 'Editar compra #'.$compra->id : 'Registrar compra'">
    @php
        $lineasIniciales = old('lineas', $compra->exists
            ? $compra->detalle->map(fn ($d) => ['producto_id' => $d->producto_id, 'cantidad' => $d->cantidad, 'costo_unitario' => (float) $d->costo_unitario])->all()
            : [['producto_id' => '', 'cantidad' => 1, 'costo_unitario' => '']]);
        $iva = \App\Models\ParametroSistema::valor('iva_porcentaje', 12);
    @endphp

    <a href="{{ route('admin.compras.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a compras</a>

    <form method="POST" action="{{ $compra->exists ? route('admin.compras.update', $compra) : route('admin.compras.store') }}"
          x-data="formCompra($el)"
          data-inicial="{{ json_encode(['lineas' => array_values($lineasIniciales), 'productos' => $productos, 'iva' => (float) $iva]) }}"
          class="mt-4 space-y-6">
        @csrf
        @if ($compra->exists)
            @method('PUT')
        @endif

        <section class="grid gap-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <x-input-label for="proveedor_id" value="Proveedor" />
                <select id="proveedor_id" name="proveedor_id" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Selecciona…</option>
                    @foreach ($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}" @selected((int) old('proveedor_id', $compra->proveedor_id) === $proveedor->id)>{{ $proveedor->nombre }} ({{ $proveedor->nit }})</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('proveedor_id')" class="mt-1" />
                <a href="{{ route('admin.proveedores.create') }}" class="mt-1 inline-block text-xs text-marca-600 hover:underline">+ Nuevo proveedor</a>
            </div>
            <div>
                <x-input-label for="serie_factura" value="Serie" />
                <x-text-input id="serie_factura" name="serie_factura" class="mt-1 block w-full" :value="old('serie_factura', $compra->serie_factura)" />
            </div>
            <div>
                <x-input-label for="numero_factura" value="Número de factura" />
                <x-text-input id="numero_factura" name="numero_factura" class="mt-1 block w-full" :value="old('numero_factura', $compra->numero_factura)" required />
                <x-input-error :messages="$errors->get('numero_factura')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="fecha_factura" value="Fecha de la factura" />
                <x-text-input id="fecha_factura" name="fecha_factura" type="date" class="mt-1 block w-full" :value="old('fecha_factura', $compra->fecha_factura?->toDateString())" :max="today()->toDateString()" required />
                <x-input-error :messages="$errors->get('fecha_factura')" class="mt-1" />
            </div>
            <div class="sm:col-span-3">
                <x-input-label for="notas" value="Notas" />
                <x-text-input id="notas" name="notas" class="mt-1 block w-full" :value="old('notas', $compra->notas)" />
            </div>
        </section>

        <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">Productos <span class="text-xs font-normal text-slate-500">(costo unitario sin IVA)</span></h2>
                <button type="button" @click="agregar()" class="text-sm font-semibold text-marca-600 hover:underline">+ Agregar fila</button>
            </div>
            <x-input-error :messages="array_merge($errors->get('lineas'), $errors->get('lineas.*'), $errors->get('lineas.*.producto_id'), $errors->get('lineas.*.cantidad'), $errors->get('lineas.*.costo_unitario'))" class="px-5 pt-3" />
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                        <tr><th class="px-5 py-2">Producto</th><th class="w-28 px-2 py-2">Cantidad</th><th class="w-36 px-2 py-2">Costo unitario</th><th class="w-32 px-2 py-2 text-right">Subtotal</th><th class="w-10"></th></tr>
                    </thead>
                    <tbody>
                        <template x-for="(linea, i) in lineas" :key="i">
                            <tr class="border-t border-slate-100">
                                <td class="px-5 py-2">
                                    <select :name="'lineas[' + i + '][producto_id]'" x-model="linea.producto_id" @change="sugerirCosto(linea)" aria-label="Producto" class="w-full rounded-lg border-slate-300 text-sm" required>
                                        <option value="">Selecciona…</option>
                                        <template x-for="p in productos" :key="p.id">
                                            <option :value="p.id" x-text="p.texto" :selected="p.id == linea.producto_id"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-2 py-2"><input type="number" min="1" :name="'lineas[' + i + '][cantidad]'" x-model="linea.cantidad" aria-label="Cantidad" class="w-full rounded-lg border-slate-300 text-sm" required></td>
                                <td class="px-2 py-2"><input type="number" min="0.01" step="0.01" :name="'lineas[' + i + '][costo_unitario]'" x-model="linea.costo_unitario" aria-label="Costo unitario" class="w-full rounded-lg border-slate-300 text-sm" required></td>
                                <td class="px-2 py-2 text-right font-medium" x-text="q(importe(linea))"></td>
                                <td class="px-2 py-2"><button type="button" @click="quitar(i)" class="text-slate-400 hover:text-acento-600" aria-label="Quitar fila"><x-icono nombre="cerrar" class="size-4" /></button></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="border-t border-slate-200 text-sm">
                        <tr><td colspan="3" class="px-5 py-1.5 text-right text-slate-500">Subtotal</td><td class="px-2 py-1.5 text-right" x-text="q(subtotal)"></td><td></td></tr>
                        <tr><td colspan="3" class="px-5 py-1.5 text-right text-slate-500">IVA ({{ $iva }}%) crédito fiscal</td><td class="px-2 py-1.5 text-right" x-text="q(subtotal * iva / 100)"></td><td></td></tr>
                        <tr><td colspan="3" class="px-5 py-2 text-right font-semibold">Total factura</td><td class="px-2 py-2 text-right font-bold" x-text="q(subtotal * (1 + iva / 100))"></td><td></td></tr>
                    </tfoot>
                </table>
            </div>
        </section>

        <div class="flex items-center gap-3">
            <x-primary-button>Guardar borrador</x-primary-button>
            <span class="text-xs text-slate-500">La mercadería entra al inventario cuando marques la compra como recibida.</span>
        </div>
    </form>
</x-admin-layout>
