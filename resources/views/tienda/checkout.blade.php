<x-app-layout title="Finalizar compra">
    @php
        $principal = $direcciones->firstWhere('es_principal', true) ?? $direcciones->first();
        $tarifas = $direcciones->mapWithKeys(fn ($d) => [$d->id => (float) $d->departamento->tarifa_envio]);
        $base = $resumen['subtotal'] + $resumen['armado'];
        $nitInicial = old('nit', $usuario->nit ?: 'CF');
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col justify-between gap-3 pb-5 md:flex-row md:items-center">
            <nav class="flex items-center gap-2 text-sm" aria-label="Progreso de compra">
                <a href="{{ route('carrito') }}" class="text-slate-500 hover:text-slate-900">Carrito</a>
                <span class="text-slate-300">/</span>
                <span class="flex items-center gap-1.5 font-display font-bold text-slate-900"><span class="size-2 rounded-full bg-marca-600"></span> Finalizar compra</span>
                <span class="text-slate-300">/</span>
                <span class="text-slate-400">Pago</span>
            </nav>
            <div class="inline-flex items-center gap-2 rounded-lg bg-slate-200/70 px-3 py-1.5 text-xs text-slate-800">
                <span class="flex size-6 items-center justify-center rounded-full bg-marca-100 text-marca-600"><x-icono nombre="candado" class="size-3.5" /></span>
                Conexión cifrada · Los datos de tu tarjeta solo los recibe PayPal
            </div>
        </div>

        <form method="POST" action="{{ route('checkout.store') }}"
              x-data="{
                  entrega: @js(old('metodo_entrega', $principal ? 'ENVIO' : 'RECOGER')),
                  direccion: @js((string) old('direccion_id', $principal?->id)),
                  pago: @js(old('metodo_pago', $paypalDisponible ? 'PAYPAL' : 'TRANSFERENCIA')),
                  nit: @js($nitInicial),
                  tarifas: @js($tarifas),
                  base: {{ $base }},
                  get envio() { return this.entrega === 'ENVIO' ? (this.tarifas[this.direccion] ?? 0) : 0 },
                  get esCf() { return this.nit.trim().toUpperCase() === 'CF' },
                  q(v) { return 'Q' + v.toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) },
              }"
              class="grid items-start gap-6 pb-10 lg:grid-cols-12">
            @csrf

            <div class="flex min-w-0 flex-col gap-6 lg:col-span-8">
                {{-- 1. Entrega --}}
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6">
                    <div class="mb-5 flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-marca-800 font-display font-bold text-white">1</span>
                            <div>
                                <h2 class="font-display text-xl font-bold text-slate-900">Método de entrega</h2>
                                <p class="text-xs text-slate-500">Recíbelo en tu casa o recógelo en nuestra tienda.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="relative cursor-pointer rounded-lg p-4 transition" :class="entrega === 'ENVIO' ? 'bg-marca-100/60 ring-2 ring-marca-600' : 'bg-slate-100/80 hover:bg-slate-200/60'">
                            <input type="radio" name="metodo_entrega" value="ENVIO" x-model="entrega" class="sr-only">
                            <span class="flex items-start justify-between gap-2">
                                <span class="flex items-center gap-3">
                                    <x-icono nombre="camion" class="size-6 text-marca-600" />
                                    <span>
                                        <span class="block font-display font-bold text-slate-900">Envío a domicilio</span>
                                        <span class="text-xs text-slate-500">Tarifa según el departamento · con número de guía</span>
                                    </span>
                                </span>
                                <span class="size-4 shrink-0 rounded-full" :class="entrega === 'ENVIO' ? 'bg-marca-600 ring-4 ring-marca-600/20' : 'bg-slate-300'"></span>
                            </span>
                        </label>
                        <label class="relative cursor-pointer rounded-lg p-4 transition" :class="entrega === 'RECOGER' ? 'bg-marca-100/60 ring-2 ring-marca-600' : 'bg-slate-100/80 hover:bg-slate-200/60'">
                            <input type="radio" name="metodo_entrega" value="RECOGER" x-model="entrega" class="sr-only">
                            <span class="flex items-start justify-between gap-2">
                                <span class="flex items-center gap-3">
                                    <x-icono nombre="tienda" class="size-6 text-slate-600" />
                                    <span>
                                        <span class="flex items-center gap-1.5"><span class="font-display font-bold text-slate-900">Recoger en tienda</span><span class="rounded bg-cian-300 px-1.5 py-0.5 text-[10px] font-bold text-cian-900 uppercase">Gratis</span></span>
                                        <span class="text-xs text-slate-500">Ciudad de Guatemala</span>
                                    </span>
                                </span>
                                <span class="size-4 shrink-0 rounded-full" :class="entrega === 'RECOGER' ? 'bg-marca-600 ring-4 ring-marca-600/20' : 'bg-slate-300'"></span>
                            </span>
                        </label>
                    </div>

                    <div x-show="entrega === 'ENVIO'" x-cloak class="mt-5 flex flex-col gap-2">
                        <p class="flex items-center gap-1.5 pb-1 text-xs text-slate-500"><x-icono nombre="camion" class="size-4 text-marca-600" /> El costo de envío cambia según el departamento de la dirección.</p>
                        @forelse ($direcciones as $direccion)
                            <label class="block cursor-pointer rounded-lg p-4 transition" :class="direccion === '{{ $direccion->id }}' ? 'bg-marca-100/40 ring-2 ring-marca-600' : 'bg-slate-100/80 hover:bg-slate-200/60'">
                                <span class="flex items-start gap-3">
                                    <input type="radio" name="direccion_id" value="{{ $direccion->id }}" x-model="direccion" class="mt-1 text-marca-600 focus:ring-0">
                                    <span class="min-w-0 flex-1">
                                        <span class="mb-1 flex flex-wrap items-center justify-between gap-2">
                                            <span class="flex items-center gap-2">
                                                <span class="font-display font-bold text-slate-900">{{ $direccion->alias }}</span>
                                                @if ($direccion->es_principal)<span class="rounded bg-marca-100 px-1.5 py-0.5 text-[10px] font-bold text-marca-700 uppercase">Principal</span>@endif
                                            </span>
                                            <span class="font-display font-bold" :class="direccion === '{{ $direccion->id }}' ? 'text-marca-600' : 'text-slate-900'">Q{{ number_format($direccion->departamento->tarifa_envio, 2) }}</span>
                                        </span>
                                        <span class="block text-sm text-slate-600">{{ $direccion->direccion }}, {{ $direccion->municipio }}, {{ $direccion->departamento->nombre }}</span>
                                        <span class="mt-1 block text-xs text-slate-400">Recibe: {{ $direccion->nombre_receptor }} · Tel. {{ $direccion->telefono }}</span>
                                    </span>
                                </span>
                            </label>
                        @empty
                            <p class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">Aún no tienes direcciones guardadas. Agrega una para recibir tu pedido.</p>
                        @endforelse
                        <a href="{{ route('direcciones.create') }}" class="mt-1 inline-flex w-fit items-center gap-2 rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-200"><x-icono nombre="ubicacion" class="size-4.5" /> Agregar otra dirección</a>
                        <x-input-error :messages="$errors->get('direccion_id')" class="mt-1" />
                    </div>

                    <div x-show="entrega === 'RECOGER'" x-cloak class="mt-5 flex flex-col gap-4 rounded-lg bg-slate-100/80 p-5 sm:flex-row">
                        <span class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-marca-800 text-white"><x-icono nombre="tienda" class="size-8" /></span>
                        <div class="flex flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-display font-bold text-slate-900">Tienda ForjaPC</h3>
                                <span class="rounded bg-cian-300 px-1.5 py-0.5 text-[10px] font-bold text-cian-900 uppercase">Costo: Q0.00</span>
                            </div>
                            <p class="text-sm text-slate-600">Ciudad de Guatemala. Te avisamos por correo cuando tu pedido esté listo para recoger.</p>
                            <p class="text-xs text-slate-500">Al recogerlo, lleva tu DPI y el número de pedido.</p>
                        </div>
                    </div>
                </section>

                {{-- 2. Facturación --}}
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-marca-800 font-display font-bold text-white">2</span>
                        <div>
                            <h2 class="font-display text-xl font-bold text-slate-900">Datos de facturación</h2>
                            <p class="text-xs text-slate-500">Nombre y NIT con los que se registra tu compra.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-4">
                        <label class="flex cursor-pointer items-center justify-between gap-3 rounded-lg bg-slate-100/80 p-4 select-none">
                            <span class="flex items-center gap-3">
                                <input type="checkbox" :checked="esCf" @change="nit = $event.target.checked ? 'CF' : ''; $nextTick(() => ! esCf && $refs.nit.focus())" class="size-5 rounded text-marca-600 focus:ring-0">
                                <span class="font-display font-bold text-slate-900">Consumidor final (CF)</span>
                            </span>
                            <span class="hidden text-xs text-slate-500 sm:inline">Si no necesitas factura con tu NIT</span>
                        </label>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label for="nit" class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">NIT <span class="text-red-600">*</span></label>
                                <input id="nit" name="nit" x-model="nit" x-ref="nit" required placeholder="Ej. 1234567-8 o CF" class="w-full rounded-lg border-0 bg-slate-100 p-3 text-sm uppercase placeholder:normal-case focus:bg-white focus:ring-2 focus:ring-marca-600">
                                <x-input-error :messages="$errors->get('nit')" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="nombre_facturacion" class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">Nombre para la factura <span class="text-red-600">*</span></label>
                                <input id="nombre_facturacion" name="nombre_facturacion" value="{{ old('nombre_facturacion', $usuario->nombre) }}" required class="w-full rounded-lg border-0 bg-slate-100 p-3 text-sm focus:bg-white focus:ring-2 focus:ring-marca-600">
                                <x-input-error :messages="$errors->get('nombre_facturacion')" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="telefono" class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">Teléfono de contacto <span class="text-red-600">*</span></label>
                                <input id="telefono" name="telefono" type="tel" value="{{ old('telefono', $usuario->telefono ?? $principal?->telefono) }}" required class="w-full rounded-lg border-0 bg-slate-100 p-3 text-sm focus:bg-white focus:ring-2 focus:ring-marca-600">
                                <x-input-error :messages="$errors->get('telefono')" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="notas" class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">Notas para el pedido (opcional)</label>
                                <input id="notas" name="notas" value="{{ old('notas') }}" maxlength="500" placeholder="Ej. Llamar antes de entregar" class="w-full rounded-lg border-0 bg-slate-100 p-3 text-sm focus:bg-white focus:ring-2 focus:ring-marca-600">
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 3. Pago --}}
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-marca-800 font-display font-bold text-white">3</span>
                        <div>
                            <h2 class="font-display text-xl font-bold text-slate-900">Forma de pago</h2>
                            <p class="text-xs text-slate-500">Pagas en el siguiente paso, desde la página de tu pedido.</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <label @class(['rounded-xl p-5 transition', 'cursor-pointer' => $paypalDisponible, 'opacity-50' => ! $paypalDisponible])
                               :class="pago === 'PAYPAL' ? 'bg-marca-100/40 ring-2 ring-marca-600' : 'bg-slate-100/80 hover:bg-slate-200/60'">
                            <span class="flex items-start justify-between gap-3">
                                <span class="flex items-center gap-3">
                                    <input type="radio" name="metodo_pago" value="PAYPAL" x-model="pago" @disabled(! $paypalDisponible) class="size-5 text-marca-600 focus:ring-0">
                                    <span>
                                        <span class="block font-display font-bold text-slate-900">PayPal o tarjeta de débito / crédito</span>
                                        <span class="text-xs text-slate-500">{{ $paypalDisponible ? 'Pagas en la ventana segura de PayPal; no necesitas cuenta para usar tu tarjeta.' : 'No disponible por el momento.' }}</span>
                                    </span>
                                </span>
                                <span class="hidden shrink-0 items-center gap-1 sm:flex">
                                    @foreach (['VISA', 'MC', 'PayPal'] as $marca)
                                        <span @class(['rounded px-1.5 py-0.5 text-[10px] font-black tracking-tighter', 'bg-marca-100 text-marca-600' => $marca === 'PayPal', 'bg-white text-slate-800' => $marca !== 'PayPal'])>{{ $marca }}</span>
                                    @endforeach
                                </span>
                            </span>
                            @if ($paypalDisponible)
                                <span x-show="pago === 'PAYPAL'" x-cloak class="mt-4 flex items-center gap-2 rounded-lg bg-white/70 p-3 text-xs text-slate-800">
                                    <x-icono nombre="candado" class="size-5 shrink-0 text-marca-600" />
                                    <span><strong>Tienes {{ $minutosPaypal }} minutos</strong> para completar el pago en línea; después se liberan las unidades apartadas.</span>
                                </span>
                            @endif
                        </label>

                        <label class="cursor-pointer rounded-xl p-5 transition" :class="pago === 'TRANSFERENCIA' ? 'bg-marca-100/40 ring-2 ring-marca-600' : 'bg-slate-100/80 hover:bg-slate-200/60'">
                            <span class="flex items-start justify-between gap-3">
                                <span class="flex items-center gap-3">
                                    <input type="radio" name="metodo_pago" value="TRANSFERENCIA" x-model="pago" class="size-5 text-marca-600 focus:ring-0">
                                    <span>
                                        <span class="block font-display font-bold text-slate-900">Transferencia o depósito bancario</span>
                                        <span class="text-xs text-slate-500">Subes la foto de tu comprobante y lo verificamos.</span>
                                    </span>
                                </span>
                            </span>
                            <span x-show="pago === 'TRANSFERENCIA'" x-cloak class="mt-4 flex flex-col gap-3">
                                <span class="flex items-center gap-2 rounded-lg bg-white/70 p-3 text-xs text-slate-800">
                                    <x-icono nombre="candado" class="size-5 shrink-0 text-marca-600" />
                                    <span><strong>Tienes {{ $horasTransferencia }} horas</strong> para transferir y subir tu comprobante; después se liberan las unidades apartadas.</span>
                                </span>
                                <span class="rounded-lg bg-white p-4">
                                    <span class="block text-[11px] font-bold tracking-wide text-slate-500 uppercase">Cuenta para depositar</span>
                                    <span class="mt-1 block font-bold text-slate-900 select-all">{{ $cuentaBancaria }}</span>
                                    <span class="mt-1 block text-xs text-slate-500">Escribe tu número de pedido como referencia.</span>
                                </span>
                            </span>
                        </label>
                        <x-input-error :messages="$errors->get('metodo_pago')" />
                    </div>
                </section>
            </div>

            {{-- Resumen --}}
            <aside class="flex flex-col gap-4 rounded-xl bg-white p-6 shadow-md ring-1 ring-slate-200/70 lg:sticky lg:top-44 lg:col-span-4">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg font-bold text-slate-900">Tu pedido</h2>
                    <span class="text-xs text-slate-500">{{ $resumen['articulos'] }} {{ $resumen['articulos'] === 1 ? 'artículo' : 'artículos' }}</span>
                </div>
                <ul class="flex flex-col gap-2 text-sm">
                    @foreach ($resumen['lineas'] as $linea)
                        <li class="flex justify-between gap-3 rounded bg-slate-100/70 px-3 py-2">
                            <span class="min-w-0 text-slate-700">
                                @if ($linea['tipo'] === 'CONFIGURACION')<span class="block text-[10px] font-bold tracking-wide text-marca-600 uppercase">PC a la medida</span>@endif
                                {{ $linea['cantidad'] > 1 ? $linea['cantidad'].'× ' : '' }}{{ $linea['descripcion'] }}
                            </span>
                            <span class="font-semibold whitespace-nowrap text-slate-900">Q{{ number_format($linea['subtotal'], 2) }}</span>
                        </li>
                    @endforeach
                </ul>
                <dl class="flex flex-col gap-2 border-t border-slate-200 pt-3 text-sm text-slate-600">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd class="font-semibold text-slate-900">Q{{ number_format($resumen['subtotal'], 2) }}</dd></div>
                    @if ($resumen['armado'] > 0)
                        <div class="flex justify-between"><dt>Armado y pruebas</dt><dd class="font-semibold text-slate-900">Q{{ number_format($resumen['armado'], 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt>Envío</dt><dd class="font-semibold text-slate-900" x-text="envio > 0 ? q(envio) : 'Gratis'"></dd></div>
                    <div class="flex items-baseline justify-between border-t border-slate-200 pt-3">
                        <dt class="font-display text-base font-bold text-slate-900">Total</dt>
                        <dd class="font-display text-2xl font-extrabold text-slate-900" x-text="q(base + envio)"></dd>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500"><dt>Base imponible</dt><dd x-text="q((base + envio) / 1.12)"></dd></div>
                    <div class="flex justify-between text-xs text-slate-500"><dt>IVA incluido (12 %)</dt><dd x-text="q((base + envio) - (base + envio) / 1.12)"></dd></div>
                </dl>

                <label class="flex items-start gap-2 rounded-lg bg-slate-100/80 p-3 text-xs text-slate-600">
                    <input type="checkbox" name="acepto" value="1" @checked(old('acepto')) class="mt-0.5 rounded border-slate-300 text-marca-600" required>
                    Acepto que las unidades se apartan solo durante el plazo de pago y que, si no pago a tiempo, el pedido se cancela.
                </label>
                <x-input-error :messages="$errors->get('acepto')" />

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-linear-to-r from-acento-500 to-acento-600 py-3.5 font-display text-base font-bold text-white shadow-lg shadow-orange-500/30 transition hover:to-acento-700 active:scale-[0.99]">
                    <x-icono nombre="candado" class="size-5" /> Confirmar pedido
                </button>
                <a href="{{ route('carrito') }}" class="text-center text-sm font-semibold text-marca-600 hover:underline">← Volver al carrito</a>
            </aside>
        </form>
    </div>
</x-app-layout>
