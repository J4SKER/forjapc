<x-app-layout :title="$producto->nombre">
    @php
        $resenas = $producto->resenasAprobadas;
        $filas = $especificacion ? collect($especificacion->filas()) : collect();
        $columnas = $filas->isEmpty() ? [] : $filas->chunk((int) ceil($filas->count() / 2))->all();
        $garantia = $producto->garantia_meses >= 12 && $producto->garantia_meses % 12 === 0
            ? ($producto->garantia_meses / 12).' '.($producto->garantia_meses === 12 ? 'año' : 'años')
            : $producto->garantia_meses.' meses';
        $estrellas = fn (float $valor, string $tamano) => collect(range(1, 5))->map(fn ($i) => '<span class="'.$tamano.' '.($i <= round($valor) ? 'text-amber-500' : 'text-slate-300').'" aria-hidden="true">★</span>')->implode('');
    @endphp

    <div class="relative mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute -top-10 left-1/4 -z-10 size-96 rounded-full bg-marca-100/40 blur-3xl"></div>

        <nav class="mb-5 flex items-center gap-1 text-xs text-slate-500" aria-label="Ruta">
            <a href="{{ route('inicio') }}" class="flex items-center gap-1 hover:text-marca-600"><x-icono nombre="tienda" class="size-4" /> Inicio</a>
            <x-icono nombre="chevron-derecha" class="size-3.5 text-slate-400" />
            <a href="{{ route('tienda.categoria', $producto->categoria->slug) }}" class="hover:text-marca-600">{{ $producto->categoria->nombre }}</a>
            <x-icono nombre="chevron-derecha" class="size-3.5 text-slate-400" />
            <span class="max-w-xs truncate font-semibold text-slate-800 sm:max-w-md">{{ $producto->nombre }}</span>
        </nav>

        {{-- Compra --}}
        <div class="grid items-start gap-6 lg:grid-cols-12 lg:gap-10">
            {{-- Galería --}}
            <div class="flex flex-col gap-3 lg:col-span-7" x-data="{ actual: 0, total: {{ max(1, $producto->imagenes->count()) }} }">
                <div class="group relative flex aspect-4/3 items-center justify-center overflow-hidden rounded-xl bg-white p-6 shadow-md sm:aspect-16/11">
                    <div class="absolute top-4 left-4 z-10 flex items-center gap-1.5">
                        @if ($producto->enOferta())
                            <span class="rounded-full bg-acento-600 px-2.5 py-1 text-[11px] font-bold tracking-wider text-white uppercase shadow-sm">-{{ $producto->porcentajeDescuento() }}%</span>
                        @endif
                        <span class="flex items-center gap-1 rounded-full bg-slate-200/70 px-2 py-0.5 text-[11px] text-slate-700">
                            <x-icono :nombre="$producto->categoria->icono" class="size-3.5 text-marca-600" /> {{ $producto->categoria->nombre }}
                        </span>
                    </div>

                    @forelse ($producto->imagenes as $indice => $imagen)
                        <img x-show="actual === {{ $indice }}" @if ($indice > 0) x-cloak @endif src="{{ $imagen->url() }}" alt="{{ $producto->nombre }}"
                             class="h-full w-full object-contain transition duration-300 group-hover:scale-105">
                    @empty
                        <x-icono :nombre="$producto->categoria->icono" class="size-48 text-slate-300" stroke-width="0.8" />
                    @endforelse

                    @if ($producto->imagenes->count() > 1)
                        <div class="pointer-events-none absolute inset-x-2 flex justify-between">
                            <button type="button" @click="actual = (actual - 1 + total) % total" class="pointer-events-auto flex size-10 items-center justify-center rounded-full bg-white/90 shadow-md hover:bg-white active:scale-90" aria-label="Imagen anterior"><x-icono nombre="chevron-izquierda" /></button>
                            <button type="button" @click="actual = (actual + 1) % total" class="pointer-events-auto flex size-10 items-center justify-center rounded-full bg-white/90 shadow-md hover:bg-white active:scale-90" aria-label="Imagen siguiente"><x-icono nombre="chevron-derecha" /></button>
                        </div>
                    @endif
                </div>

                @if ($producto->imagenes->count() > 1)
                    <div class="grid grid-cols-4 gap-2 sm:gap-3">
                        @foreach ($producto->imagenes as $indice => $imagen)
                            <button type="button" @click="actual = {{ $indice }}" class="aspect-4/3 overflow-hidden rounded-lg bg-white p-2 shadow-sm transition hover:bg-slate-50"
                                    :class="actual === {{ $indice }} && 'ring-2 ring-marca-600'" aria-label="Ver imagen {{ $indice + 1 }}">
                                <img src="{{ $imagen->url() }}" alt="" class="h-full w-full object-contain">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Datos y compra --}}
            <div class="flex flex-col gap-5 rounded-xl bg-white p-5 shadow-md sm:p-8 lg:col-span-5">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold tracking-widest text-marca-600 uppercase">{{ $producto->marca->nombre }}</span>
                        <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-500">SKU: {{ $producto->sku }}</span>
                    </div>
                    <h1 class="mt-1 font-display text-2xl font-extrabold tracking-tight text-slate-900 sm:text-3xl">{{ $producto->nombre }}</h1>
                    @if ($calificacion)
                        <a href="#resenas" class="mt-1 flex items-center gap-2 text-sm">
                            <span class="flex text-lg leading-none">{!! $estrellas($calificacion, 'text-lg') !!}</span>
                            <span class="font-bold text-slate-900">{{ $calificacion }}</span>
                            <span class="text-slate-500">({{ $resenas->count() }} {{ $resenas->count() === 1 ? 'opinión' : 'opiniones' }})</span>
                        </a>
                    @endif
                </div>

                <div class="flex flex-col gap-2 rounded-xl bg-slate-100/80 p-4">
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <span class="font-display text-3xl font-extrabold tracking-tight text-slate-900">Q{{ number_format($producto->precio, 2) }}</span>
                        @if ($producto->enOferta())
                            <span class="font-medium text-slate-500 line-through">Q{{ number_format($producto->precio_anterior, 2) }}</span>
                            <span class="rounded-full bg-acento-600/15 px-2 py-0.5 text-xs font-bold text-acento-700">Ahorras Q{{ number_format($producto->precio_anterior - $producto->precio, 2) }} · {{ $producto->porcentajeDescuento() }}%</span>
                        @endif
                    </div>
                    <p class="flex items-center gap-1.5 text-xs font-medium text-slate-500"><x-icono nombre="lista" class="size-4 text-marca-600" /> Precio con IVA incluido</p>
                </div>

                @if ($producto->estaDisponible())
                    <p class="flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700">
                        <span class="relative flex size-3">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-600 opacity-75 motion-reduce:animate-none"></span>
                            <span class="relative inline-flex size-3 rounded-full bg-emerald-600"></span>
                        </span>
                        @if ($producto->stockBajo())
                            ¡Últimas {{ $producto->disponible() }} unidades para entrega inmediata!
                        @else
                            En existencia ({{ $producto->disponible() }} {{ $producto->disponible() === 1 ? 'unidad' : 'unidades' }} para entrega inmediata)
                        @endif
                    </p>
                @else
                    <p class="flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700"><span class="size-3 rounded-full bg-red-500"></span> Sin existencia por el momento</p>
                @endif

                @include('tienda.partials.agregar-al-carrito', ['producto' => $producto])

                @if ($producto->tipoComponente())
                    <a href="{{ route('tienda.configurador') }}" class="group flex items-center justify-between rounded-xl bg-slate-100/80 p-4 shadow-sm transition hover:bg-slate-200/70">
                        <span class="flex items-center gap-3">
                            <span class="flex size-10 items-center justify-center rounded-lg bg-marca-600 text-white transition group-hover:scale-110"><x-icono nombre="herramienta" /></span>
                            <span>
                                <span class="block font-display text-sm font-bold text-slate-900">Arma tu PC con esta pieza</span>
                                <span class="block text-xs text-slate-500">El configurador te muestra solo lo compatible</span>
                            </span>
                        </span>
                        <x-icono nombre="chevron-derecha" class="text-marca-600 transition group-hover:translate-x-1" />
                    </a>
                @endif

                <ul class="flex flex-col gap-3">
                    @foreach ([
                        ['escudo', 'Garantía de '.$garantia, 'Producto nuevo; te atendemos directamente en Ciudad de Guatemala.'],
                        ['camion', 'Opciones de entrega', 'Envío a los 22 departamentos con número de guía, o recoge en tienda sin costo.'],
                        ['tarjeta', 'Formas de pago', 'PayPal, tarjeta de débito o crédito, o transferencia bancaria.'],
                    ] as [$icono, $titulo, $texto])
                        <li class="flex items-start gap-3">
                            <x-icono :nombre="$icono" class="mt-0.5 size-5.5 shrink-0 text-marca-600" />
                            <span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $titulo }}</span>
                                <span class="block text-xs text-slate-500">{{ $texto }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Especificaciones --}}
        @if ($filas->isNotEmpty() || $producto->descripcion)
            <section class="mt-10">
                <span class="text-xs font-bold tracking-widest text-marca-600 uppercase">Hoja de datos</span>
                <h2 class="font-display text-xl font-bold text-slate-900 sm:text-2xl">Especificaciones técnicas</h2>

                @if ($filas->isNotEmpty())
                    <div class="mt-3 overflow-hidden rounded-xl bg-white shadow-md">
                        <dl class="grid md:grid-cols-2">
                            @foreach ($columnas as $columna)
                                <div class="flex flex-col">
                                    @foreach ($columna->values() as $n => $valor)
                                        <div @class(['flex items-center justify-between gap-4 px-4 py-3.5 text-sm', 'bg-slate-100/70' => $n % 2 === 0])>
                                            <dt class="font-medium text-slate-500">{{ $columna->keys()[$n] }}</dt>
                                            <dd class="text-right font-bold text-slate-900">{{ $valor }}</dd>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endif

                @if ($producto->descripcion)
                    <div class="mt-4 rounded-xl bg-white p-5 shadow-md">
                        <h3 class="font-display font-bold text-slate-900">Descripción</h3>
                        <p class="mt-1 text-sm leading-relaxed whitespace-pre-line text-slate-600">{{ $producto->descripcion }}</p>
                    </div>
                @endif
            </section>
        @endif

        {{-- Opiniones --}}
        <section class="mt-10 rounded-xl bg-white p-5 shadow-md sm:p-8" id="resenas">
            <div class="flex flex-col justify-between gap-5 md:flex-row md:items-center">
                <div class="flex items-center gap-4">
                    <div class="flex size-20 shrink-0 flex-col items-center justify-center rounded-2xl bg-marca-100">
                        <span class="font-display text-3xl leading-none font-black text-marca-600">{{ $calificacion ?? '—' }}</span>
                        <span class="text-[11px] font-bold text-marca-600">DE 5</span>
                    </div>
                    <div>
                        @if ($calificacion)
                            <span class="flex leading-none">{!! $estrellas($calificacion, 'text-xl') !!}</span>
                        @endif
                        <h2 class="mt-1 font-display text-lg font-bold text-slate-900">Opiniones de clientes</h2>
                        <p class="text-sm text-slate-500">
                            {{ $resenas->isEmpty() ? 'Aún no hay opiniones de este producto.' : 'Basado en '.$resenas->count().' '.($resenas->count() === 1 ? 'opinión' : 'opiniones').' de clientes que lo recibieron.' }}
                        </p>
                    </div>
                </div>
                @if ($resenas->isNotEmpty())
                    @php
                        $cinco = (int) round($resenas->where('calificacion', 5)->count() / $resenas->count() * 100);
                    @endphp
                    <div class="flex w-full flex-col gap-1 rounded-lg bg-slate-100/80 p-3 md:w-64">
                        <div class="flex items-center justify-between text-xs text-slate-500"><span>5 estrellas</span><span class="font-bold text-slate-900">{{ $cinco }}%</span></div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200"><div class="h-2 rounded-full bg-amber-500" style="width: {{ $cinco }}%"></div></div>
                    </div>
                @endif
            </div>

            @if ($resenas->isNotEmpty())
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @foreach ($resenas as $resena)
                        <article class="flex flex-col justify-between gap-3 rounded-xl bg-slate-100/70 p-4">
                            <div class="flex flex-col gap-2">
                                <div class="flex items-center justify-between">
                                    <span class="flex leading-none" aria-label="{{ $resena->calificacion }} de 5">{!! $estrellas($resena->calificacion, 'text-base') !!}</span>
                                    <time class="text-[11px] text-slate-500">{{ $resena->created_at->translatedFormat('j M Y') }}</time>
                                </div>
                                @if ($resena->compra_verificada)
                                    <span class="inline-flex w-fit items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700"><x-icono nombre="escudo" class="size-3.5" /> Compra verificada</span>
                                @endif
                                <h3 class="font-display text-sm font-bold text-slate-900">{{ $resena->titulo }}</h3>
                                <p class="text-sm text-slate-700">{{ $resena->comentario }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="flex size-7 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">{{ mb_strtoupper(mb_substr($resena->usuario->nombre, 0, 1)) }}</span>
                                <span class="text-sm font-bold text-slate-900">{{ Str::before($resena->usuario->nombre, ' ') }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif

            <div class="max-w-xl">
                @include('tienda.partials.formulario-resena', ['producto' => $producto])
            </div>
        </section>

        {{-- Relacionados --}}
        @if ($relacionados->isNotEmpty())
            <section class="mt-10">
                <span class="text-xs font-bold tracking-widest text-marca-600 uppercase">{{ $producto->categoria->nombre }}</span>
                <h2 class="mb-3 font-display text-xl font-bold text-slate-900 sm:text-2xl">También te puede interesar</h2>
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                    @foreach ($relacionados as $relacionado)
                        <x-producto-card :producto="$relacionado" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
