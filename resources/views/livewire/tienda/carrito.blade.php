@php
    $configuraciones = collect($resumen['lineas'])->where('tipo', 'CONFIGURACION');
    $productos = collect($resumen['lineas'])->where('tipo', 'PRODUCTO');
    $problemas = collect($resumen['lineas'])->whereNotNull('problema');
@endphp

<div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-end">
        <div class="flex flex-col gap-1">
            <nav class="flex items-center gap-1 text-xs text-slate-500" aria-label="Ruta">
                <a href="{{ route('inicio') }}" class="hover:text-marca-600">Inicio</a>
                <x-icono nombre="chevron-derecha" class="size-3.5 text-slate-400" />
                <span class="font-medium text-slate-800">Carrito de compras</span>
            </nav>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Carrito de compras</h1>
                @if ($resumen['articulos'] > 0)
                    <span class="flex items-center gap-1.5 rounded bg-marca-100 px-2 py-1 text-[11px] font-bold tracking-wider text-marca-700 uppercase">
                        <span class="size-1.5 rounded-full bg-marca-600"></span> {{ $resumen['articulos'] }} {{ $resumen['articulos'] === 1 ? 'artículo' : 'artículos' }}
                    </span>
                @endif
            </div>
        </div>
        <a href="{{ route('inicio') }}" class="group inline-flex items-center gap-1 font-display text-sm font-semibold text-marca-600 hover:text-marca-500">
            <x-icono nombre="chevron-izquierda" class="size-4.5 transition group-hover:-translate-x-1" /> Seguir comprando
        </a>
    </div>

    @if ($resumen['lineas'] === [])
        <div class="mx-auto flex max-w-xl flex-col items-center gap-4 rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-slate-200/70">
            <span class="relative flex size-20 items-center justify-center rounded-full bg-slate-200/70 shadow-inner">
                <x-icono nombre="carrito" class="size-10 text-slate-400" />
                <span class="absolute top-3 right-3 size-3 animate-ping rounded-full bg-marca-600 motion-reduce:animate-none"></span>
            </span>
            <div>
                <h2 class="font-display text-lg font-bold text-slate-900">Tu carrito está vacío</h2>
                <p class="mt-1 max-w-md text-sm text-slate-600">Explora nuestras piezas o crea tu equipo desde cero con el configurador: te mostramos solo lo que es compatible.</p>
            </div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <a href="{{ route('tienda.configurador') }}" class="flex items-center justify-center gap-2 rounded-lg bg-linear-to-r from-acento-500 to-acento-600 px-5 py-2.5 font-display text-sm font-bold text-white shadow-md shadow-orange-500/30 hover:to-acento-700">
                    <x-icono nombre="herramienta" class="size-4.5" /> Armar una PC ahora
                </a>
                <a href="{{ route('tienda.buscar') }}" class="flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 font-display text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">
                    <x-icono nombre="lista" class="size-4.5" /> Explorar el catálogo
                </a>
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <div class="flex flex-col gap-6 lg:col-span-8" wire:loading.class="opacity-60">
                {{-- Avisos de existencia --}}
                @foreach ($problemas as $linea)
                    <div class="flex items-start gap-3 rounded-lg bg-amber-50 p-4 shadow-sm ring-1 ring-amber-200">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded bg-white text-amber-500 shadow-sm">!</span>
                        <div>
                            <p class="font-display text-sm font-bold text-slate-900">Atención con el inventario</p>
                            <p class="text-sm text-slate-700"><strong>{{ $linea['descripcion'] }}:</strong> {{ $linea['problema'] }}</p>
                        </div>
                    </div>
                @endforeach

                {{-- PCs armadas --}}
                @foreach ($configuraciones as $linea)
                    @php
                        $item = $linea['item'];
                    @endphp
                    <article wire:key="item-{{ $item->id }}" class="flex flex-col overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-slate-200/70">
                        <div class="flex flex-col justify-between gap-4 bg-slate-100/80 p-5 md:flex-row md:items-center">
                            <div class="flex items-start gap-4">
                                <span class="flex size-12 shrink-0 items-center justify-center rounded bg-marca-600 text-white shadow-sm"><x-icono nombre="monitor" class="size-7" /></span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="rounded bg-marca-800 px-2 py-0.5 text-[11px] font-bold text-cian-400 uppercase">PC a la medida</span>
                                        @if (! $linea['problema'])
                                            <span class="inline-flex items-center gap-1 rounded bg-slate-200 px-2 py-0.5 text-[11px] font-bold text-slate-800"><x-icono nombre="escudo" class="size-3.5 text-marca-600" /> Compatible</span>
                                        @endif
                                    </div>
                                    <h2 class="mt-1 font-display text-lg font-bold text-slate-900">{{ $linea['descripcion'] }}</h2>
                                    <span class="text-xs text-slate-500">{{ count($linea['componentes']) }} piezas{{ $linea['cantidad'] > 1 ? ' · '.$linea['cantidad'].' equipos' : '' }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col md:items-end">
                                <span class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">Subtotal de la PC</span>
                                <span class="font-display text-2xl font-extrabold tracking-tight text-slate-900">Q{{ number_format($linea['subtotal'] + $linea['armado'], 2) }}</span>
                            </div>
                        </div>

                        @if ($linea['armado'] > 0)
                            <div class="flex items-center gap-2 bg-slate-200/50 px-5 py-2.5 text-xs font-semibold text-slate-800">
                                <x-icono nombre="escudo" class="size-4.5 text-marca-600" /> Incluye armado y pruebas en nuestro taller
                            </div>
                        @endif

                        <div class="flex flex-col gap-2 p-5">
                            <div class="flex items-center justify-between pb-1 text-[11px] font-bold tracking-wider text-slate-500 uppercase">
                                <span>Componentes</span><span>Precio</span>
                            </div>
                            @foreach ($linea['componentes'] as $componente)
                                <div class="flex items-center justify-between gap-4 rounded bg-slate-100/60 px-2 py-2 text-sm transition hover:bg-slate-100">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex size-6 shrink-0 items-center justify-center rounded bg-slate-200/70"><x-icono :nombre="$componente['producto']->categoria->icono" class="size-4 text-slate-600" /></span>
                                        <span class="flex min-w-0 flex-col">
                                            <span class="truncate font-medium text-slate-900">{{ $componente['cantidad'] > 1 ? $componente['cantidad'].'× ' : '' }}{{ $componente['producto']->nombre }}</span>
                                            <span class="truncate text-[11px] text-slate-500">{{ $componente['producto']->categoria->nombre }}{{ ($resumenEsp = $componente['producto']->especificacion()?->resumen()) ? ' · '.$resumenEsp : '' }}</span>
                                        </span>
                                    </div>
                                    <span class="shrink-0 font-semibold text-slate-900">Q{{ number_format($componente['producto']->precio * $componente['cantidad'], 2) }}</span>
                                </div>
                            @endforeach
                            @if ($linea['armado'] > 0)
                                <div class="flex items-center justify-between gap-4 rounded bg-slate-200/60 px-2 py-2 text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-6 items-center justify-center rounded bg-marca-100"><x-icono nombre="herramienta" class="size-4 text-marca-600" /></span>
                                        <span class="flex flex-col">
                                            <span class="font-semibold text-slate-900">Servicio de armado y pruebas</span>
                                            <span class="text-[11px] text-slate-500">Cableado ordenado y pruebas de encendido, temperatura y estabilidad</span>
                                        </span>
                                    </div>
                                    <span class="shrink-0 font-bold text-marca-600">Q{{ number_format($linea['armado'], 2) }}</span>
                                </div>
                            @endif

                            <div class="mt-2 flex flex-col items-center justify-between gap-3 pt-2 sm:flex-row">
                                <span class="rounded bg-slate-100 px-2 py-1 text-sm text-slate-600">Cantidad: <strong class="text-slate-900">{{ $linea['cantidad'] }} {{ $linea['cantidad'] === 1 ? 'equipo' : 'equipos' }}</strong></span>
                                <button type="button" wire:click="quitar({{ $item->id }})" wire:confirm="¿Quitar esta PC del carrito?" class="inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-red-600">
                                    <x-icono nombre="cerrar" class="size-4.5" /> Quitar PC
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach

                {{-- Productos sueltos --}}
                @if ($productos->isNotEmpty())
                    <section class="flex flex-col gap-3">
                        @if ($configuraciones->isNotEmpty())
                            <div class="flex items-center justify-between">
                                <h2 class="font-display text-lg font-bold text-slate-900">Productos individuales</h2>
                                <span class="text-xs text-slate-500">{{ $productos->count() }} {{ $productos->count() === 1 ? 'producto' : 'productos' }}</span>
                            </div>
                        @endif

                        @foreach ($productos as $linea)
                            @php
                                $item = $linea['item'];
                                $producto = $linea['producto'];
                                $maximo = $producto ? min(10, max($linea['cantidad'], $producto->disponible())) : $linea['cantidad'];
                            @endphp
                            <article wire:key="item-{{ $item->id }}" class="flex flex-col items-center justify-between gap-4 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200/70 md:flex-row">
                                <div class="flex w-full items-center gap-4 md:w-auto">
                                    <div class="flex size-24 shrink-0 items-center justify-center overflow-hidden rounded bg-slate-100 p-2">
                                        @if ($producto && ($url = $producto->imagenPrincipalUrl()))
                                            <img src="{{ $url }}" alt="" class="h-full w-full object-contain">
                                        @else
                                            <x-icono :nombre="$producto?->categoria->icono ?? 'paquete'" class="size-10 text-slate-300" stroke-width="1.25" />
                                        @endif
                                    </div>
                                    <div class="flex min-w-0 flex-col">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">{{ $producto?->marca?->nombre }}</span>
                                            @if ($producto && $producto->disponible() === $linea['cantidad'] && ! $linea['problema'])
                                                <span class="rounded bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 uppercase">¡Última{{ $linea['cantidad'] > 1 ? 's' : '' }} disponible{{ $linea['cantidad'] > 1 ? 's' : '' }}!</span>
                                            @endif
                                        </div>
                                        @if ($producto)
                                            <a href="{{ route('tienda.producto', $producto->slug) }}" class="font-display font-semibold text-slate-900 hover:text-marca-600">{{ $linea['descripcion'] }}</a>
                                        @else
                                            <span class="font-display font-semibold text-slate-500">{{ $linea['descripcion'] }}</span>
                                        @endif
                                        <div class="mt-1 flex flex-wrap items-center gap-2">
                                            @if ($resumenEsp = $producto?->especificacion()?->resumen())
                                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ $resumenEsp }}</span>
                                            @endif
                                            @if ($producto?->estaDisponible() && ! $linea['problema'])
                                                <span class="flex items-center gap-1 text-[11px] font-semibold text-emerald-700"><span class="size-1.5 rounded-full bg-emerald-600"></span> En existencia</span>
                                            @endif
                                        </div>
                                        <span class="mt-1 text-xs text-slate-500">Precio unitario: <span class="font-medium text-slate-900">Q{{ number_format($linea['precio'], 2) }}</span></span>
                                    </div>
                                </div>
                                <div class="flex w-full items-center justify-between gap-6 md:w-auto md:justify-end">
                                    <div class="flex items-center rounded bg-slate-100 p-1" role="group" aria-label="Cantidad de {{ $linea['descripcion'] }}">
                                        <button type="button" wire:click="cambiarCantidad({{ $item->id }}, {{ $linea['cantidad'] - 1 }})" @disabled($linea['cantidad'] <= 1)
                                                class="flex size-7 items-center justify-center rounded bg-white font-bold text-slate-900 shadow-xs hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Disminuir">−</button>
                                        <span class="w-10 text-center font-bold text-slate-900">{{ $linea['cantidad'] }}</span>
                                        <button type="button" wire:click="cambiarCantidad({{ $item->id }}, {{ $linea['cantidad'] + 1 }})" @disabled($linea['cantidad'] >= $maximo)
                                                @if ($linea['cantidad'] >= $maximo) title="No hay más unidades disponibles" @endif
                                                class="flex size-7 items-center justify-center rounded bg-white font-bold text-slate-900 shadow-xs hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" aria-label="Aumentar">+</button>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="font-display text-xl font-bold tracking-tight text-slate-900">Q{{ number_format($linea['subtotal'], 2) }}</span>
                                        <button type="button" wire:click="quitar({{ $item->id }})" class="mt-0.5 inline-flex items-center gap-0.5 text-sm text-slate-500 transition hover:text-red-600">
                                            <x-icono nombre="cerrar" class="size-4" /> Quitar
                                        </button>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </section>
                @endif
            </div>

            {{-- Resumen --}}
            <aside class="flex flex-col gap-4 lg:sticky lg:top-44 lg:col-span-4">
                <div class="flex flex-col gap-4 rounded-lg bg-white p-6 shadow-md ring-1 ring-slate-200/70">
                    <div class="flex items-center justify-between">
                        <h2 class="font-display text-lg font-bold text-slate-900">Resumen de compra</h2>
                        <x-icono nombre="lista" class="size-5 text-slate-400" />
                    </div>
                    <dl class="flex flex-col gap-2 text-sm text-slate-600">
                        <div class="flex items-center justify-between"><dt>Productos ({{ $resumen['articulos'] }})</dt><dd class="font-semibold text-slate-900">Q{{ number_format($resumen['subtotal'], 2) }}</dd></div>
                        @if ($resumen['armado'] > 0)
                            <div class="flex items-center justify-between"><dt>Armado y pruebas</dt><dd class="font-semibold text-slate-900">Q{{ number_format($resumen['armado'], 2) }}</dd></div>
                        @endif
                        <div class="mt-1 flex items-center gap-2 rounded bg-slate-100 p-2 text-xs"><x-icono nombre="camion" class="size-4 text-marca-600" /> El envío se calcula en el siguiente paso</div>
                    </dl>
                    <div class="h-px bg-slate-200"></div>
                    <div class="flex items-baseline justify-between">
                        <span class="font-display font-bold text-slate-900">Total</span>
                        <span class="flex flex-col items-end">
                            <span class="font-display text-2xl font-extrabold tracking-tight text-slate-900">Q{{ number_format($resumen['subtotal'] + $resumen['armado'], 2) }}</span>
                            <span class="text-[11px] text-slate-500">IVA incluido</span>
                        </span>
                    </div>

                    @if ($resumen['hay_problemas'])
                        <p class="rounded bg-red-50 px-3 py-2 text-xs text-red-700">Corrige los artículos con aviso de inventario para continuar.</p>
                        <span class="flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-slate-200 py-3 font-display font-bold text-slate-500">Continuar compra →</span>
                    @else
                        <a href="{{ route('checkout') }}" class="flex w-full items-center justify-center gap-2 rounded-lg bg-linear-to-r from-acento-500 to-acento-600 py-3 font-display font-bold text-white shadow-lg shadow-orange-500/30 transition hover:to-acento-700 active:scale-[0.99]">
                            Continuar compra <x-icono nombre="chevron-derecha" class="size-5" />
                        </a>
                    @endif

                    <ul class="flex flex-col gap-1 pt-1">
                        @foreach ([['tarjeta', 'Pago seguro con PayPal, tarjeta o transferencia'], ['escudo', 'Garantía y soporte en Guatemala'], ['camion', 'Envío a los 22 departamentos o recoge en tienda']] as [$icono, $texto])
                            <li class="flex items-center gap-2 rounded p-1.5 text-xs text-slate-600">
                                <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-slate-100"><x-icono :nombre="$icono" class="size-4 text-marca-600" /></span> {{ $texto }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    @endif
</div>
