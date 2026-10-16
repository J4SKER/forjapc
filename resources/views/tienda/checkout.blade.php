<x-app-layout title="Finalizar compra">
    @php
        $principal = $direcciones->first();
        $tarifas = $direcciones->mapWithKeys(fn ($d) => [$d->id => (float) $d->departamento->tarifa_envio]);
        $base = $resumen['subtotal'] + $resumen['armado'];
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <a href="{{ route('carrito') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver al carrito</a>
        <h1 class="mt-1 text-2xl font-extrabold text-slate-900 sm:text-3xl">Finalizar compra</h1>

        <form method="POST" action="{{ route('checkout.store') }}"
              x-data="{
                  entrega: @js(old('metodo_entrega', $principal ? 'ENVIO' : 'RECOGER')),
                  direccion: @js((string) old('direccion_id', $principal?->id)),
                  pago: @js(old('metodo_pago', $paypalDisponible ? 'PAYPAL' : 'TRANSFERENCIA')),
                  tarifas: @js($tarifas),
                  base: {{ $base }},
                  get envio() { return this.entrega === 'ENVIO' ? (this.tarifas[this.direccion] ?? 0) : 0 },
                  q(v) { return 'Q' + v.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
              }"
              class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
            @csrf

            <div class="space-y-6">
                {{-- Entrega --}}
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">1. Entrega</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer gap-3 rounded-xl border p-4" :class="entrega === 'ENVIO' ? 'border-marca-500 bg-marca-50' : 'border-slate-200'">
                            <input type="radio" name="metodo_entrega" value="ENVIO" x-model="entrega" class="mt-1 text-marca-600">
                            <span><span class="block font-semibold">Envío a domicilio</span><span class="text-xs text-slate-500">Tarifa según el departamento</span></span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-xl border p-4" :class="entrega === 'RECOGER' ? 'border-marca-500 bg-marca-50' : 'border-slate-200'">
                            <input type="radio" name="metodo_entrega" value="RECOGER" x-model="entrega" class="mt-1 text-marca-600">
                            <span><span class="block font-semibold">Recoger en tienda</span><span class="text-xs text-slate-500">Ciudad de Guatemala · sin costo</span></span>
                        </label>
                    </div>

                    <div x-show="entrega === 'ENVIO'" x-cloak class="mt-4">
                        @forelse ($direcciones as $direccion)
                            <label class="mt-2 flex cursor-pointer gap-3 rounded-lg border p-3 text-sm" :class="direccion === '{{ $direccion->id }}' ? 'border-marca-500' : 'border-slate-200'">
                                <input type="radio" name="direccion_id" value="{{ $direccion->id }}" x-model="direccion" class="mt-0.5 text-marca-600">
                                <span class="flex-1">
                                    <span class="font-semibold">{{ $direccion->alias }}</span> · {{ $direccion->nombre_receptor }}<br>
                                    <span class="text-slate-600">{{ $direccion->direccion }}, {{ $direccion->municipio }}, {{ $direccion->departamento->nombre }}</span>
                                </span>
                                <span class="font-medium whitespace-nowrap">Q{{ number_format($direccion->departamento->tarifa_envio, 2) }}</span>
                            </label>
                        @empty
                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">Aún no tienes direcciones guardadas.</p>
                        @endforelse
                        <a href="{{ route('direcciones.create') }}" class="mt-2 inline-block text-sm font-semibold text-marca-600 hover:underline">+ Agregar una dirección</a>
                        <x-input-error :messages="$errors->get('direccion_id')" class="mt-2" />
                    </div>
                </section>

                {{-- Facturación --}}
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">2. Datos de facturación</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <x-input-label for="nombre_facturacion" value="Nombre para la factura" />
                            <x-text-input id="nombre_facturacion" name="nombre_facturacion" class="mt-1 block w-full" :value="old('nombre_facturacion', $usuario->nombre)" required />
                            <x-input-error :messages="$errors->get('nombre_facturacion')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="nit" value="NIT (o CF)" />
                            <x-text-input id="nit" name="nit" class="mt-1 block w-full uppercase" :value="old('nit', $usuario->nit)" required />
                            <x-input-error :messages="$errors->get('nit')" class="mt-1" />
                        </div>
                        <div>
                            <x-input-label for="telefono" value="Teléfono de contacto" />
                            <x-text-input id="telefono" name="telefono" type="tel" class="mt-1 block w-full" :value="old('telefono', $usuario->telefono ?? $principal?->telefono)" required />
                            <x-input-error :messages="$errors->get('telefono')" class="mt-1" />
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="notas" value="Notas para el pedido (opcional)" />
                            <x-text-input id="notas" name="notas" class="mt-1 block w-full" :value="old('notas')" maxlength="500" />
                        </div>
                    </div>
                </section>

                {{-- Pago --}}
                <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">3. Forma de pago</h2>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <label @class(['flex gap-3 rounded-xl border p-4', 'cursor-pointer' => $paypalDisponible, 'opacity-50' => ! $paypalDisponible])
                               :class="pago === 'PAYPAL' ? 'border-marca-500 bg-marca-50' : 'border-slate-200'">
                            <input type="radio" name="metodo_pago" value="PAYPAL" x-model="pago" @disabled(! $paypalDisponible) class="mt-1 text-marca-600">
                            <span>
                                <span class="block font-semibold">PayPal o tarjeta</span>
                                <span class="text-xs text-slate-500">{{ $paypalDisponible ? "Pagas en la ventana segura de PayPal. Tienes {$minutosPaypal} minutos." : 'No disponible por el momento.' }}</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer gap-3 rounded-xl border p-4" :class="pago === 'TRANSFERENCIA' ? 'border-marca-500 bg-marca-50' : 'border-slate-200'">
                            <input type="radio" name="metodo_pago" value="TRANSFERENCIA" x-model="pago" class="mt-1 text-marca-600">
                            <span><span class="block font-semibold">Transferencia o depósito</span><span class="text-xs text-slate-500">Subes tu comprobante y lo verificamos. Tienes {{ $horasTransferencia }} horas.</span></span>
                        </label>
                    </div>
                    <p x-show="pago === 'TRANSFERENCIA'" x-cloak class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">Cuenta para depositar: <strong>{{ $cuentaBancaria }}</strong></p>
                    <x-input-error :messages="$errors->get('metodo_pago')" class="mt-2" />
                </section>
            </div>

            {{-- Resumen --}}
            <aside class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-24">
                <h2 class="font-bold text-slate-900">Tu pedido</h2>
                <ul class="space-y-1.5 text-sm">
                    @foreach ($resumen['lineas'] as $linea)
                        <li class="flex justify-between gap-3">
                            <span class="text-slate-600">{{ $linea['cantidad'] > 1 ? $linea['cantidad'].'× ' : '' }}{{ $linea['descripcion'] }}</span>
                            <span class="whitespace-nowrap">Q{{ number_format($linea['subtotal'], 2) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-1.5 border-t border-slate-100 pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd>Q{{ number_format($resumen['subtotal'], 2) }}</dd></div>
                    @if ($resumen['armado'] > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Armado y pruebas</dt><dd>Q{{ number_format($resumen['armado'], 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-slate-500">Envío</dt><dd x-text="envio > 0 ? q(envio) : 'Gratis'"></dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold" x-text="q(base + envio)"></dd></div>
                    <div class="flex justify-between text-xs text-slate-500"><dt>IVA incluido (12 %)</dt><dd x-text="q((base + envio) - (base + envio) / 1.12)"></dd></div>
                </dl>

                <label class="flex items-start gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="acepto" value="1" @checked(old('acepto')) class="mt-0.5 rounded border-slate-300 text-marca-600" required>
                    Acepto que las unidades se apartan solo durante el plazo de pago y que, si no pago a tiempo, el pedido se cancela.
                </label>
                <x-input-error :messages="$errors->get('acepto')" />

                <x-primary-button class="w-full py-3 text-base">Confirmar pedido</x-primary-button>
            </aside>
        </form>
    </div>
</x-app-layout>
