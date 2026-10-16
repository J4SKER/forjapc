@use('App\Enums\EstadoPedido')
@use('App\Enums\MetodoPago')
<x-app-layout :title="'Pedido '.$pedido->numero">
    @php

        $etapas = [EstadoPedido::PendientePago, EstadoPedido::Pagado, $pedido->ordenesArmado->isNotEmpty() ? EstadoPedido::EnArmado : EstadoPedido::EnPreparacion, EstadoPedido::ListoEntrega];
        if ($pedido->esEnvio()) {
            $etapas[] = EstadoPedido::Enviado;
        }
        $etapas[] = EstadoPedido::Entregado;
        $alcanzados = $pedido->historial->pluck('estado_nuevo')->all();
        $porVerificar = $pedido->pagos->firstWhere(fn ($p) => $p->estado === \App\Enums\EstadoPago::Pendiente && $p->comprobante_ruta);
    @endphp

    <x-slot name="header">
        <a href="{{ route('pedidos.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Mis pedidos</a>
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-extrabold text-slate-900">Pedido {{ $pedido->numero }}</h1>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $pedido->estado->color() }}">{{ $pedido->estado->etiqueta() }}</span>
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(0,1fr)_22rem] lg:px-8">
        <div class="space-y-6">
            {{-- Seguimiento --}}
            @unless (in_array($pedido->estado, [EstadoPedido::Cancelado, EstadoPedido::Reembolsado], true))
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <ol class="flex flex-wrap gap-y-4">
                        @foreach ($etapas as $indice => $etapa)
                            @php($hecho = in_array($etapa, $alcanzados, true))
                            <li class="flex min-w-24 flex-1 items-center gap-2">
                                <span @class(['flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold', 'bg-emerald-500 text-white' => $hecho, 'bg-slate-200 text-slate-500' => ! $hecho])>{{ $hecho ? '✓' : $indice + 1 }}</span>
                                <span @class(['text-xs font-medium', 'text-slate-900' => $hecho, 'text-slate-400' => ! $hecho])>{{ $etapa->etiqueta() }}</span>
                            </li>
                        @endforeach
                    </ol>
                    @if ($pedido->numero_guia)
                        <p class="mt-4 rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-900">Enviado con <strong>{{ $pedido->mensajeria }}</strong> · Guía <strong>{{ $pedido->numero_guia }}</strong></p>
                    @endif
                </section>
            @else
                <p class="rounded-xl bg-slate-100 px-4 py-3 text-sm text-slate-700">Este pedido está {{ mb_strtolower($pedido->estado->etiqueta()) }}. {{ $pedido->motivo_cancelacion }}</p>
            @endunless

            {{-- Pago pendiente --}}
            @if ($pedido->estaPendienteDePago())
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-2 ring-acento-500">
                    <h2 class="text-lg font-bold text-slate-900">Completa tu pago</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Total: <strong>Q{{ number_format($pedido->total, 2) }}</strong>.
                        Las unidades están apartadas hasta el <strong>{{ $pedido->vence_at->translatedFormat('j \d\e F, H:i') }}</strong>
                        <span x-data="{ s: {{ max(0, now()->diffInSeconds($pedido->vence_at, false)) }} }" x-init="setInterval(() => s = Math.max(0, s - 1), 1000)"
                              x-text="'(' + (s > 3600 ? Math.floor(s / 3600) + ' h ' : '') + Math.floor((s % 3600) / 60) + ' min ' + (s % 60) + ' s)'"></span>.
                    </p>

                    @if ($pedido->metodo_pago === MetodoPago::Paypal)
                        @if ($paypalClientId)
                            <div class="mt-5 max-w-md" x-data="pagoPaypal('{{ route('pedidos.paypal.orden', $pedido) }}', '{{ route('pedidos.paypal.capturar', $pedido) }}')">
                                <div id="paypal-botones"></div>
                                <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm text-red-600"></p>
                                <p x-show="procesando" x-cloak class="mt-2 text-sm text-slate-600">Confirmando el pago…</p>
                            </div>
                            <p class="mt-3 text-xs text-slate-500">Modo de pruebas (sandbox): usa una cuenta de comprador de PayPal Developer. No se cobra dinero real.</p>
                            <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency=USD&intent=capture&locale=es_XC"></script>
                            <script>
                                function pagoPaypal(urlOrden, urlCaptura) {
                                    return {
                                        error: null,
                                        procesando: false,
                                        init() {
                                            const token = document.querySelector('meta[name=csrf-token]').content;
                                            const enviar = (url, cuerpo = {}) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: JSON.stringify(cuerpo) })
                                                .then(async r => { const d = await r.json(); if (! r.ok) throw new Error(d.error || 'Error'); return d; });

                                            paypal.Buttons({
                                                createOrder: () => enviar(urlOrden).then(d => d.id).catch(e => { this.error = e.message; throw e; }),
                                                onApprove: (datos) => { this.procesando = true; return enviar(urlCaptura, { orderID: datos.orderID }).then(d => window.location = d.redireccion).catch(e => { this.procesando = false; this.error = e.message; }); },
                                                onError: () => { this.error = 'PayPal no pudo procesar el pago. Intenta de nuevo.'; },
                                            }).render('#paypal-botones');
                                        },
                                    };
                                }
                            </script>
                        @else
                            <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">El pago con PayPal no está disponible en este momento. Puedes pagar por transferencia usando el formulario de abajo.</p>
                        @endif
                    @endif

                    @if ($pedido->metodo_pago === MetodoPago::Transferencia || ! $paypalClientId)
                        <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm">
                            <p>Deposita o transfiere <strong>Q{{ number_format($pedido->total, 2) }}</strong> a: <strong>{{ $cuentaBancaria }}</strong>. Escribe el número de pedido <strong>{{ $pedido->numero }}</strong> como referencia.</p>
                        </div>

                        @if ($porVerificar)
                            <p class="mt-4 rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-900">Recibimos tu comprobante ({{ $porVerificar->banco }}, boleta {{ $porVerificar->numero_boleta }}). Lo estamos verificando.</p>
                        @else
                            <form method="POST" action="{{ route('pedidos.transferencia', $pedido) }}" enctype="multipart/form-data" class="mt-4 grid gap-4 sm:grid-cols-3">
                                @csrf
                                <div>
                                    <x-input-label for="banco" value="Banco" />
                                    <select id="banco" name="banco" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" required>
                                        @foreach (['Banco Industrial', 'Banrural', 'BAM', 'G&T Continental', 'Banco Promerica', 'BAC Credomatic', 'Otro'] as $banco)
                                            <option @selected(old('banco') === $banco)>{{ $banco }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="numero_boleta" value="No. de boleta o referencia" />
                                    <x-text-input id="numero_boleta" name="numero_boleta" class="mt-1 block w-full" :value="old('numero_boleta')" required />
                                </div>
                                <div>
                                    <x-input-label for="monto" value="Monto depositado (Q)" />
                                    <x-text-input id="monto" name="monto" type="number" step="0.01" class="mt-1 block w-full" :value="old('monto', $pedido->total)" required />
                                </div>
                                <div class="sm:col-span-3">
                                    <x-input-label for="comprobante" value="Foto o PDF del comprobante (máx. 4 MB)" />
                                    <input id="comprobante" type="file" name="comprobante" accept="image/*,application/pdf" required class="mt-1 block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-marca-50 file:px-3 file:py-2 file:font-semibold file:text-marca-700">
                                </div>
                                <x-input-error :messages="$errors->all()" class="sm:col-span-3" />
                                <div class="sm:col-span-3"><x-primary-button>Enviar comprobante</x-primary-button></div>
                            </form>
                        @endif
                    @endif

                    <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}" class="mt-5 border-t border-slate-100 pt-4" onsubmit="return confirm('¿Cancelar este pedido?')">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-slate-500 hover:text-acento-600">Cancelar pedido</button>
                    </form>
                </section>
            @endif

            {{-- Artículos --}}
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                <h2 class="text-lg font-bold text-slate-900">Artículos</h2>
                <div class="mt-3 divide-y divide-slate-100">
                    @foreach ($pedido->itemsAgrupados() as $grupo => $items)
                        @if ($items->first()->grupo_configuracion)
                            <div class="py-3">
                                <p class="font-semibold text-slate-900">{{ $items->first()->nombre_configuracion }} <span class="text-xs font-normal text-slate-500">PC a la medida{{ $items->first()->con_armado ? ' · con armado' : '' }}</span></p>
                                <ul class="mt-1 space-y-0.5 text-sm text-slate-600">
                                    @foreach ($items as $item)
                                        <li class="flex justify-between gap-3"><span>{{ $item->cantidad > 1 ? $item->cantidad.'× ' : '' }}{{ $item->descripcion }}</span><span>Q{{ number_format($item->subtotal, 2) }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            @foreach ($items as $item)
                                <div class="flex justify-between gap-3 py-3 text-sm">
                                    <span>{{ $item->cantidad }}× <a href="{{ route('tienda.producto', $item->producto->slug) }}" class="font-medium text-slate-900 hover:text-marca-700">{{ $item->descripcion }}</a></span>
                                    <span class="font-medium">Q{{ number_format($item->subtotal, 2) }}</span>
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </section>
        </div>

        <aside class="space-y-4">
            <section class="rounded-2xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <dl class="space-y-1.5">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>Q{{ number_format($pedido->subtotal, 2) }}</dd></div>
                    @if ($pedido->costo_armado > 0)<div class="flex justify-between"><dt class="text-slate-500">Armado</dt><dd>Q{{ number_format($pedido->costo_armado, 2) }}</dd></div>@endif
                    <div class="flex justify-between"><dt class="text-slate-500">Envío</dt><dd>{{ $pedido->costo_envio > 0 ? 'Q'.number_format($pedido->costo_envio, 2) : 'Sin costo' }}</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold">Q{{ number_format($pedido->total, 2) }}</dd></div>
                    <div class="flex justify-between text-xs text-slate-500"><dt>Base imponible / IVA</dt><dd>Q{{ number_format($pedido->base_imponible, 2) }} / Q{{ number_format($pedido->iva, 2) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Entrega y facturación</h2>
                @if ($pedido->esEnvio())
                    <p class="mt-2 text-slate-600">{{ $pedido->direccion_envio['nombre_receptor'] }} · {{ $pedido->direccion_envio['telefono'] }}<br>{{ $pedido->direccion_envio['direccion'] }}, {{ $pedido->direccion_envio['municipio'] }}, {{ $pedido->direccion_envio['departamento'] }}</p>
                @else
                    <p class="mt-2 text-slate-600">Recoger en tienda, Ciudad de Guatemala.</p>
                @endif
                <p class="mt-2 text-slate-600">Factura a: {{ $pedido->nombre_facturacion }} · NIT {{ $pedido->nit }}</p>
                <p class="mt-1 text-slate-600">Pago: {{ $pedido->metodo_pago->etiqueta() }}</p>
            </section>

            <section class="rounded-2xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Historial</h2>
                <ol class="mt-2 space-y-2">
                    @foreach ($pedido->historial as $registro)
                        <li><span class="font-medium">{{ $registro->estado_nuevo->etiqueta() }}</span> <span class="text-xs text-slate-400">{{ $registro->created_at->format('d/m/Y H:i') }}</span>
                            @if ($registro->comentario)<span class="block text-xs text-slate-500">{{ $registro->comentario }}</span>@endif</li>
                    @endforeach
                </ol>
            </section>
        </aside>
    </div>
</x-app-layout>
