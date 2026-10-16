<x-app-layout>
    @php
        $banners = [
            [
                'etiqueta' => 'Configurador inteligente',
                'titulo' => 'Arma tu PC a la medida',
                'texto' => 'Elige cada pieza y nosotros validamos la compatibilidad por ti: socket, memoria, tamaño de la tarjeta de video y potencia de la fuente.',
                'boton' => 'Empezar a armar',
                'ruta' => route('tienda.configurador'),
                'fondo' => 'from-marca-800 via-marca-700 to-marca-500',
                'icono' => 'herramienta',
            ],
            [
                'etiqueta' => 'Rendimiento gamer',
                'titulo' => 'Tarjetas de video y procesadores',
                'texto' => 'Lo último en GPUs y CPUs con existencias reales y precios actualizados.',
                'boton' => 'Ver tarjetas de video',
                'ruta' => route('tienda.categoria', 'tarjetas-de-video'),
                'fondo' => 'from-acento-700 via-acento-600 to-orange-500',
                'icono' => 'gpu',
            ],
            [
                'etiqueta' => 'Cobertura nacional',
                'titulo' => 'Envíos a los 22 departamentos',
                'texto' => 'Paga en línea con PayPal o por transferencia y sigue tu pedido hasta la puerta de tu casa.',
                'boton' => 'Ver monitores',
                'ruta' => route('tienda.categoria', 'monitores'),
                'fondo' => 'from-slate-900 via-slate-800 to-marca-600',
                'icono' => 'camion',
            ],
        ];
    @endphp

    {{-- Carrusel principal --}}
    <section class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8"
             x-data="{ actual: 0, total: {{ count($banners) }}, siguiente() { this.actual = (this.actual + 1) % this.total }, anterior() { this.actual = (this.actual - 1 + this.total) % this.total } }"
             x-init="setInterval(() => siguiente(), 6000)" aria-roledescription="carrusel">
        <div class="relative overflow-hidden rounded-2xl shadow-lg">
            @foreach ($banners as $indice => $banner)
                <div x-show="actual === {{ $indice }}" @if ($indice > 0) x-cloak @endif
                     x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0"
                     class="relative bg-linear-to-r {{ $banner['fondo'] }} text-white">
                    <div class="grid min-h-64 items-center gap-6 px-8 py-10 sm:min-h-80 sm:px-14 md:grid-cols-5">
                        <div class="md:col-span-3">
                            <span class="inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide uppercase">{{ $banner['etiqueta'] }}</span>
                            <h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">{{ $banner['titulo'] }}</h2>
                            <p class="mt-3 max-w-xl text-sm text-white/85 sm:text-base">{{ $banner['texto'] }}</p>
                            <a href="{{ $banner['ruta'] }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-white px-5 py-2.5 text-sm font-bold text-slate-900 shadow hover:bg-slate-100">
                                {{ $banner['boton'] }} <x-icono nombre="chevron-derecha" class="size-4" />
                            </a>
                        </div>
                        <div class="hidden justify-center md:col-span-2 md:flex">
                            <x-icono :nombre="$banner['icono']" class="size-48 text-white/25" stroke-width="1" />
                        </div>
                    </div>
                </div>
            @endforeach

            <button type="button" @click="anterior()" class="absolute top-1/2 left-3 -translate-y-1/2 rounded-full bg-black/25 p-2 text-white hover:bg-black/40" aria-label="Anterior">
                <x-icono nombre="chevron-izquierda" />
            </button>
            <button type="button" @click="siguiente()" class="absolute top-1/2 right-3 -translate-y-1/2 rounded-full bg-black/25 p-2 text-white hover:bg-black/40" aria-label="Siguiente">
                <x-icono nombre="chevron-derecha" />
            </button>
            <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
                @foreach ($banners as $indice => $banner)
                    <button type="button" @click="actual = {{ $indice }}" class="h-2 rounded-full bg-white transition-all"
                            :class="actual === {{ $indice }} ? 'w-6' : 'w-2 opacity-50'" aria-label="Ir a la diapositiva {{ $indice + 1 }}"></button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Categorías destacadas --}}
    <section class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mb-4 flex items-end justify-between">
            <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">Compra por categoría</h2>
        </div>
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 lg:grid-cols-6">
            @foreach (\App\Models\Categoria::menu() as $categoria)
                <a href="{{ route('tienda.categoria', $categoria['slug']) }}"
                   class="group flex flex-col items-center gap-2 rounded-xl border border-slate-200 bg-white px-2 py-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-marca-500 hover:shadow-md">
                    <span class="rounded-full bg-marca-50 p-3 text-marca-600 transition group-hover:bg-marca-600 group-hover:text-white">
                        <x-icono :nombre="$categoria['icono']" class="size-7" />
                    </span>
                    <span class="text-xs font-semibold text-slate-700 sm:text-sm">{{ $categoria['nombre'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Ofertas --}}
    @if ($ofertas->isNotEmpty())
        <section class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex items-end justify-between">
                <h2 class="flex items-center gap-2 text-xl font-bold text-slate-900 sm:text-2xl">
                    <span class="rounded-md bg-acento-600 px-2 py-0.5 text-sm font-bold text-white">OFERTAS</span> Precios especiales
                </h2>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                @foreach ($ofertas as $producto)
                    <x-producto-card :producto="$producto" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Destacados --}}
    @if ($destacados->isNotEmpty())
        <section class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mb-4 flex items-end justify-between">
                <h2 class="text-xl font-bold text-slate-900 sm:text-2xl">Productos destacados</h2>
                <a href="{{ route('tienda.buscar') }}" class="text-sm font-semibold text-marca-600 hover:underline">Ver todo</a>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($destacados as $producto)
                    <x-producto-card :producto="$producto" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Configurador --}}
    <section class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 lg:grid-cols-2">
            <div class="p-8 sm:p-10">
                <span class="text-sm font-semibold text-acento-600">Exclusivo de ForjaPC</span>
                <h2 class="mt-2 text-2xl font-extrabold text-slate-900 sm:text-3xl">Tu computadora, pieza por pieza</h2>
                <p class="mt-3 text-slate-600">El configurador solo te muestra piezas compatibles y con existencia, calcula la fuente que necesitas y te da el precio total al instante. Si quieres, nuestro taller la arma y la prueba antes de entregártela.</p>
                <ul class="mt-6 grid gap-3 text-sm text-slate-700 sm:grid-cols-2">
                    @foreach (['Validación de compatibilidad', 'Cálculo de consumo eléctrico', 'Guarda y comparte tu configuración', 'Armado y pruebas de estrés'] as $beneficio)
                        <li class="flex items-center gap-2"><x-icono nombre="escudo" class="size-5 text-emerald-600" /> {{ $beneficio }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('tienda.configurador') }}" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-acento-600 px-6 py-3 font-semibold text-white shadow hover:bg-acento-700">
                    <x-icono nombre="herramienta" /> Arma tu PC ahora
                </a>
            </div>
            <div class="grid grid-cols-4 gap-3 bg-linear-to-br from-marca-800 to-marca-600 p-8 text-white sm:p-10">
                @foreach (['cpu', 'placa', 'ram', 'gpu', 'disco', 'fuente', 'gabinete', 'ventilador'] as $pieza)
                    <div class="flex aspect-square items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15">
                        <x-icono :nombre="$pieza" class="size-10" stroke-width="1.25" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-app-layout>
