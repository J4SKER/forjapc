<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">Tu carrito</h1>

    @if ($resumen['lineas'] === [])
        <div class="mt-6 rounded-2xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200">
            <x-icono nombre="carrito" class="mx-auto size-14 text-slate-300" />
            <p class="mt-3 text-lg font-semibold text-slate-800">Tu carrito está vacío</p>
            <p class="mt-1 text-sm text-slate-500">Explora el catálogo o arma tu PC a la medida.</p>
            <div class="mt-5 flex justify-center gap-3">
                <a href="{{ route('inicio') }}" class="rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">Ver productos</a>
                <a href="{{ route('tienda.configurador') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Arma tu PC</a>
            </div>
        </div>
    @else
        <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="divide-y divide-slate-100 rounded-2xl bg-white shadow-sm ring-1 ring-slate-200" wire:loading.class="opacity-60">
                @foreach ($resumen['lineas'] as $linea)
                    @php($item = $linea['item'])
                    <article wire:key="item-{{ $item->id }}" class="flex gap-4 p-5">
                        <div class="flex size-20 shrink-0 items-center justify-center rounded-lg bg-slate-50">
                            @if ($linea['producto'] && ($url = $linea['producto']->imagenPrincipalUrl()))
                                <img src="{{ $url }}" alt="" class="h-full w-full object-contain p-1">
                            @else
                                <x-icono :nombre="$linea['producto']?->categoria->icono ?? 'herramienta'" class="size-10 text-slate-300" />
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    @if ($linea['tipo'] === 'CONFIGURACION')
                                        <p class="text-xs font-semibold tracking-wide text-acento-600 uppercase">PC a la medida{{ $item->incluye_armado ? ' · con armado' : '' }}</p>
                                        <h2 class="font-semibold text-slate-900">{{ $linea['descripcion'] }}</h2>
                                    @else
                                        <a href="{{ route('tienda.producto', $linea['producto']->slug) }}" class="font-semibold text-slate-900 hover:text-marca-700">{{ $linea['descripcion'] }}</a>
                                        <p class="text-xs text-slate-500">Q{{ number_format($linea['precio'], 2) }} c/u</p>
                                    @endif
                                </div>
                                <p class="text-lg font-bold text-slate-900">Q{{ number_format($linea['subtotal'] + $linea['armado'], 2) }}</p>
                            </div>

                            @if ($linea['tipo'] === 'CONFIGURACION')
                                <ul class="mt-2 grid gap-0.5 text-xs text-slate-600 sm:grid-cols-2">
                                    @foreach ($linea['componentes'] as $componente)
                                        <li>{{ $componente['cantidad'] > 1 ? $componente['cantidad'].'× ' : '' }}{{ $componente['producto']->nombre }}</li>
                                    @endforeach
                                    @if ($linea['armado'] > 0)
                                        <li class="text-marca-700">Servicio de armado y pruebas · Q{{ number_format($linea['armado'], 2) }}</li>
                                    @endif
                                </ul>
                            @endif

                            @if ($linea['problema'])
                                <p class="mt-2 rounded bg-red-50 px-2 py-1 text-xs font-medium text-red-700">{{ $linea['problema'] }}</p>
                            @endif

                            <div class="mt-3 flex items-center gap-4 text-sm">
                                @if ($linea['tipo'] === 'PRODUCTO')
                                    <label class="flex items-center gap-2 text-slate-600">Cantidad
                                        <select wire:change="cambiarCantidad({{ $item->id }}, $event.target.value)" class="rounded-lg border-slate-300 py-1 text-sm">
                                            @for ($i = 1; $i <= max($linea['cantidad'], min(10, $linea['producto']?->disponible() ?? 1)); $i++)
                                                <option value="{{ $i }}" @selected($linea['cantidad'] === $i)>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </label>
                                @endif
                                <button type="button" wire:click="quitar({{ $item->id }})" class="font-medium text-acento-600 hover:underline">Quitar</button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <aside class="space-y-3 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 lg:sticky lg:top-24">
                <h2 class="font-bold text-slate-900">Resumen</h2>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Productos ({{ $resumen['articulos'] }})</dt><dd>Q{{ number_format($resumen['subtotal'], 2) }}</dd></div>
                    @if ($resumen['armado'] > 0)
                        <div class="flex justify-between"><dt class="text-slate-500">Armado y pruebas</dt><dd>Q{{ number_format($resumen['armado'], 2) }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-slate-500">Envío</dt><dd class="text-slate-500">Se calcula al elegir la entrega</dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold">Q{{ number_format($resumen['subtotal'] + $resumen['armado'], 2) }}</dd></div>
                </dl>
                <p class="text-xs text-slate-500">Precios con IVA incluido.</p>

                @if ($resumen['hay_problemas'])
                    <p class="rounded-lg bg-red-50 px-3 py-2 text-xs text-red-700">Corrige los artículos marcados en rojo para continuar.</p>
                    <span class="block w-full cursor-not-allowed rounded-lg bg-acento-600/50 px-4 py-3 text-center font-semibold text-white">Continuar con la compra</span>
                @else
                    <a href="{{ route('checkout') }}" class="block w-full rounded-lg bg-acento-600 px-4 py-3 text-center font-semibold text-white hover:bg-acento-700">Continuar con la compra</a>
                @endif
                <a href="{{ route('inicio') }}" class="block text-center text-sm font-medium text-marca-600 hover:underline">Seguir comprando</a>
            </aside>
        </div>
    @endif
</div>
