@use('App\Enums\EstadoPedido')
@use('App\Enums\EstadoPago')
<x-admin-layout :title="'Pedido '.$pedido->numero">
    @php
        $puedeGestionar = Auth::user()->tieneRolEfectivo(\App\Enums\Rol::Admin, \App\Enums\Rol::Vendedor);
    @endphp

    <a href="{{ route('admin.pedidos.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a pedidos</a>

    <div class="mt-4 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $pedido->numero }}</h2>
                        <p class="text-xs text-slate-500">{{ $pedido->created_at->format('d/m/Y H:i') }} · {{ $pedido->usuario->nombre }} ({{ $pedido->usuario->email }})</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $pedido->estado->color() }}">{{ $pedido->estado->etiqueta() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                            <tr><th class="px-5 py-2">Artículo</th><th class="px-3 py-2 text-right">Cant.</th><th class="px-3 py-2 text-right">Precio</th><th class="px-3 py-2 text-right">Costo</th><th class="px-5 py-2 text-right">Subtotal</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($pedido->itemsAgrupados() as $items)
                                @if ($items->first()->grupo_configuracion)
                                    <tr class="bg-slate-50"><td colspan="5" class="px-5 py-1.5 text-xs font-semibold text-slate-700">{{ $items->first()->nombre_configuracion }} · {{ $items->first()->con_armado ? 'con armado' : 'sin armado' }}</td></tr>
                                @endif
                                @foreach ($items as $item)
                                    <tr>
                                        <td class="px-5 py-2">{{ $item->descripcion }} <span class="text-xs text-slate-400">{{ $item->producto->sku }}</span></td>
                                        <td class="px-3 py-2 text-right">{{ $item->cantidad }}</td>
                                        <td class="px-3 py-2 text-right">Q{{ number_format($item->precio_unitario, 2) }}</td>
                                        <td class="px-3 py-2 text-right text-slate-500">{{ $item->costo_unitario !== null ? 'Q'.number_format($item->costo_unitario, 2) : '—' }}</td>
                                        <td class="px-5 py-2 text-right">Q{{ number_format($item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <tfoot class="border-t border-slate-200 text-sm">
                            <tr><td colspan="4" class="px-5 py-1 text-right text-slate-500">Subtotal</td><td class="px-5 py-1 text-right">Q{{ number_format($pedido->subtotal, 2) }}</td></tr>
                            <tr><td colspan="4" class="px-5 py-1 text-right text-slate-500">Armado</td><td class="px-5 py-1 text-right">Q{{ number_format($pedido->costo_armado, 2) }}</td></tr>
                            <tr><td colspan="4" class="px-5 py-1 text-right text-slate-500">Envío</td><td class="px-5 py-1 text-right">Q{{ number_format($pedido->costo_envio, 2) }}</td></tr>
                            <tr><td colspan="4" class="px-5 py-2 text-right font-bold">Total (base Q{{ number_format($pedido->base_imponible, 2) }} + IVA Q{{ number_format($pedido->iva, 2) }})</td><td class="px-5 py-2 text-right font-bold">Q{{ number_format($pedido->total, 2) }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </section>

            {{-- Pagos --}}
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Pagos</h2>
                @forelse ($pedido->pagos as $pago)
                    <div class="mt-3 rounded-lg border border-slate-200 p-3 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">{{ $pago->metodo->etiqueta() }} · Q{{ number_format($pago->monto, 2) }}
                                @if ($pago->monto_usd) <span class="text-xs text-slate-500">(US${{ number_format($pago->monto_usd, 2) }} a {{ $pago->tipo_cambio }})</span>@endif</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $pago->estado->color() }}">{{ $pago->estado->etiqueta() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ $pago->created_at->format('d/m/Y H:i') }}
                            @if ($pago->referencia_externa) · Ref. {{ $pago->referencia_externa }}@endif
                            @if ($pago->banco) · {{ $pago->banco }}, boleta {{ $pago->numero_boleta }}@endif
                            @if ($pago->comision > 0) · Comisión Q{{ number_format($pago->comision, 2) }}@endif
                            @if ($pago->verificador) · Verificó {{ $pago->verificador->nombre }}@endif
                        </p>
                        @if ($pago->comprobante_ruta)
                            <a href="{{ route('pagos.comprobante', $pago) }}" target="_blank" class="mt-1 inline-block text-xs font-semibold text-marca-600 hover:underline">Ver comprobante</a>
                        @endif
                        @if ($pago->motivo_rechazo)<p class="mt-1 text-xs text-red-700">Rechazo: {{ $pago->motivo_rechazo }}</p>@endif

                        @foreach ($pago->reembolsos as $reembolso)
                            <p class="mt-1 text-xs text-slate-600">↩ Reembolso Q{{ number_format($reembolso->monto, 2) }} · {{ $reembolso->motivo }} · {{ $reembolso->created_at->format('d/m/Y') }}{{ $reembolso->devuelve_inventario ? ' · mercadería devuelta al inventario' : '' }}</p>
                        @endforeach

                        @if ($puedeGestionar && $pago->estado === EstadoPago::Aprobado && $pago->saldoReembolsable() > 0)
                            <details class="mt-2">
                                <summary class="cursor-pointer text-xs font-semibold text-acento-700">Reembolsar (disponible Q{{ number_format($pago->saldoReembolsable(), 2) }})</summary>
                                <form method="POST" action="{{ route('admin.pedidos.reembolsar', [$pedido, $pago]) }}" class="mt-2 grid gap-2 sm:grid-cols-3" onsubmit="return confirm('¿Registrar el reembolso?')">
                                    @csrf
                                    <x-text-input name="monto" type="number" step="0.01" :value="$pago->saldoReembolsable()" aria-label="Monto" required />
                                    <x-text-input name="motivo" placeholder="Motivo" class="sm:col-span-2" required />
                                    <label class="flex items-center gap-2 text-xs sm:col-span-2"><input type="checkbox" name="devolver_inventario" value="1" class="rounded border-slate-300"> Devolver la mercadería al inventario (solo reembolso total)</label>
                                    <x-danger-button>Reembolsar</x-danger-button>
                                </form>
                            </details>
                        @endif
                    </div>
                @empty
                    <p class="mt-2 text-sm text-slate-500">Sin pagos registrados.</p>
                @endforelse
            </section>

            @if ($pedido->ordenesArmado->isNotEmpty())
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Órdenes de armado</h2>
                    @foreach ($pedido->ordenesArmado as $orden)
                        <a href="{{ route('admin.taller.show', $orden) }}" class="mt-2 flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50">
                            <span>{{ $orden->nombre_configuracion }} · {{ $orden->tecnico?->nombre ?? 'sin técnico' }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $orden->estado->color() }}">{{ $orden->estado->etiqueta() }}</span>
                        </a>
                    @endforeach
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <section class="rounded-xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Entrega y facturación</h2>
                @if ($pedido->esEnvio())
                    <p class="mt-2 text-slate-600">{{ $pedido->direccion_envio['nombre_receptor'] }} · {{ $pedido->direccion_envio['telefono'] }}<br>{{ $pedido->direccion_envio['direccion'] }}, {{ $pedido->direccion_envio['municipio'] }}, {{ $pedido->direccion_envio['departamento'] }}
                        @if ($pedido->direccion_envio['referencias'] ?? null)<br><span class="text-xs">Ref.: {{ $pedido->direccion_envio['referencias'] }}</span>@endif</p>
                @else
                    <p class="mt-2 text-slate-600">Recoge en tienda · Tel. {{ $pedido->telefono }}</p>
                @endif
                <p class="mt-2 text-slate-600">Factura: {{ $pedido->nombre_facturacion }} · NIT {{ $pedido->nit }}</p>
                @if ($pedido->numero_guia)<p class="mt-1 text-slate-600">Guía: {{ $pedido->mensajeria }} {{ $pedido->numero_guia }}</p>@endif
                @if ($pedido->notas)<p class="mt-1 text-xs text-slate-500">Notas: {{ $pedido->notas }}</p>@endif
                @if ($pedido->estaPendienteDePago())<p class="mt-2 text-xs text-amber-700">Vence: {{ $pedido->vence_at->format('d/m/Y H:i') }}</p>@endif
            </section>

            @if ($puedeGestionar)
                @if ($destinos !== [])
                    <form method="POST" action="{{ route('admin.pedidos.estado', $pedido) }}" class="space-y-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        @csrf
                        <h2 class="font-semibold text-slate-900">Cambiar estado</h2>
                        <select name="estado" aria-label="Nuevo estado" class="block w-full rounded-lg border-slate-300 text-sm">
                            @foreach ($destinos as $destino)
                                <option value="{{ $destino->value }}">{{ $destino->etiqueta() }}</option>
                            @endforeach
                        </select>
                        <x-text-input name="comentario" placeholder="Comentario para el cliente (opcional, va en el correo)" class="block w-full" />
                        <x-primary-button class="w-full">Actualizar estado</x-primary-button>
                    </form>
                @endif

                @if ($pedido->estado === EstadoPedido::ListoEntrega && $pedido->esEnvio())
                    <form method="POST" action="{{ route('admin.pedidos.envio', $pedido) }}" class="space-y-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                        @csrf
                        <h2 class="font-semibold text-slate-900">Registrar envío</h2>
                        <x-text-input name="mensajeria" placeholder="Empresa de mensajería" class="block w-full" required />
                        <x-text-input name="numero_guia" placeholder="Número de guía" class="block w-full" required />
                        <x-input-error :messages="$errors->get('numero_guia')" />
                        <x-primary-button class="w-full">Marcar como enviado</x-primary-button>
                    </form>
                @endif

                @if ($pedido->estaPendienteDePago())
                    <form method="POST" action="{{ route('admin.pedidos.cancelar', $pedido) }}" class="space-y-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200" onsubmit="return confirm('¿Cancelar el pedido y liberar las reservas?')">
                        @csrf
                        <h2 class="font-semibold text-slate-900">Cancelar pedido</h2>
                        <x-text-input name="motivo" placeholder="Motivo" class="block w-full" required />
                        <x-danger-button class="w-full">Cancelar pedido</x-danger-button>
                    </form>
                @endif
            @endif

            <section class="rounded-xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Historial de estados</h2>
                <ol class="mt-2 space-y-2">
                    @foreach ($pedido->historial as $registro)
                        <li>
                            <span class="font-medium">{{ $registro->estado_anterior?->etiqueta() ?? 'Nuevo' }} → {{ $registro->estado_nuevo->etiqueta() }}</span>
                            <span class="block text-xs text-slate-500">{{ $registro->created_at->format('d/m/Y H:i') }} · {{ $registro->usuario?->nombre ?? 'Sistema' }}</span>
                            @if ($registro->comentario)<span class="block text-xs text-slate-500">{{ $registro->comentario }}</span>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </aside>
    </div>
</x-admin-layout>
