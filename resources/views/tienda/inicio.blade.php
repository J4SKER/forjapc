<x-app-layout>
    @php
        $whatsapp = \App\Models\ParametroSistema::valor('whatsapp_contacto');
        $pestanaInicial = $ofertas->isNotEmpty() ? 'ofertas' : 'destacados';
    @endphp

    {{-- Portada --}}
    <section class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 sm:pt-6 lg:px-8">
        <div class="relative overflow-hidden rounded-xl bg-marca-800 p-5 text-white shadow-xl sm:p-10 lg:p-12">
            <div class="pointer-events-none absolute -top-12 -right-12 size-64 rounded-full bg-marca-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute right-0 bottom-0 size-48 rounded-full bg-cian-400/10 blur-2xl"></div>

            <div class="relative grid items-center gap-8 lg:grid-cols-5">
                <div class="flex flex-col gap-3 lg:col-span-3">
                    <span class="inline-flex items-center gap-1.5 self-start rounded-full bg-marca-500/30 px-2.5 py-1 text-cian-400">
                        <span class="relative flex size-2">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-cian-400 opacity-75 motion-reduce:animate-none"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-cian-400"></span>
                        </span>
                        <span class="text-[10px] font-bold tracking-wider uppercase">Verificación de compatibilidad automática</span>
                    </span>
                    <h1 class="font-display text-2xl leading-tight font-bold tracking-tight sm:text-4xl lg:text-5xl lg:leading-[1.1] lg:font-extrabold">
                        Arma la PC de tus sueños con precisión de taller.
                    </h1>
                    <p class="max-w-xl text-sm leading-relaxed text-marca-300 sm:text-base">
                        Elige procesador, tarjeta madre, memoria y tarjeta de video: solo te mostramos piezas compatibles y con existencia, y calculamos la fuente que necesitas. Si quieres, nuestro taller la arma y la prueba antes de entregártela.
                    </p>
                    <div class="flex flex-col gap-2 pt-2 sm:flex-row">
                        <a href="{{ route('tienda.configurador') }}" class="flex items-center justify-center gap-2 rounded-lg bg-linear-to-r from-acento-500 to-acento-600 px-5 py-3 font-display text-sm font-bold text-white shadow-lg shadow-orange-500/30 transition hover:to-acento-700 active:scale-[0.98] sm:text-base">
                            Comenzar a armar mi PC <x-icono nombre="chevron-derecha" class="size-4.5" />
                        </a>
                        <a href="{{ route('tienda.buscar') }}" class="flex items-center justify-center gap-2 rounded-lg bg-white/10 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20">
                            <x-icono nombre="buscar" class="size-4.5 text-cian-400" /> Ver todo el catálogo
                        </a>
                    </div>
                    <div class="grid grid-cols-3 gap-1.5 pt-3 sm:max-w-lg">
                        @foreach ([['escudo', 'Garantía en cada producto'], ['herramienta', 'Armado y pruebas'], ['chat', 'Atención por WhatsApp']] as [$icono, $texto])
                            <div class="flex items-center gap-1.5 rounded-lg bg-white/5 p-1.5 sm:p-2">
                                <x-icono :nombre="$icono" class="size-4 shrink-0 text-cian-400" />
                                <span class="text-[9px] leading-tight font-bold text-marca-300 sm:text-[11px]">{{ $texto }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="hidden grid-cols-4 gap-3 lg:col-span-2 lg:grid" aria-hidden="true">
                    @foreach (['cpu', 'placa', 'ram', 'gpu', 'disco', 'fuente', 'gabinete', 'ventilador'] as $pieza)
                        <div class="flex aspect-square items-center justify-center rounded-xl bg-white/5 ring-1 ring-white/10">
                            <x-icono :nombre="$pieza" class="size-10 text-cian-400/80" stroke-width="1.25" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Categorías --}}
    <section class="mx-auto mt-8 max-w-7xl px-4 sm:mt-10 sm:px-6 lg:px-8">
        <div class="mb-3 flex items-end justify-between">
            <div>
                <h2 class="font-display text-lg font-bold text-slate-900 sm:text-2xl">Explora por componente</h2>
                <p class="text-xs text-slate-500 sm:text-sm">Productos nuevos con garantía y existencias reales</p>
            </div>
            <a href="{{ route('tienda.buscar') }}" class="flex shrink-0 items-center text-sm font-bold whitespace-nowrap text-marca-600 hover:underline">Ver todo <x-icono nombre="chevron-derecha" class="size-4" /></a>
        </div>
        <div class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6 lg:gap-4">
            @foreach (\App\Models\Categoria::menu() as $categoria)
                <a href="{{ route('tienda.categoria', $categoria['slug']) }}" class="group flex flex-col rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md">
                    <span class="flex h-20 items-center justify-center rounded-lg bg-marca-800 sm:h-24">
                        <x-icono :nombre="$categoria['icono']" class="size-10 text-cian-400 transition duration-300 group-hover:scale-110" stroke-width="1.25" />
                    </span>
                    <span class="mt-2 font-display text-sm font-bold text-slate-900 group-hover:text-marca-600">{{ $categoria['nombre'] }}</span>
                    <span class="text-[11px] text-slate-500">Ver productos</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Ofertas y destacados --}}
    @if ($ofertas->isNotEmpty() || $destacados->isNotEmpty())
        <section class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8" x-data="{ pestana: '{{ $pestanaInicial }}' }">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <span class="flex items-center gap-1 text-[11px] font-bold tracking-wider text-acento-600 uppercase">
                        <x-icono nombre="tarjeta" class="size-4" /> Precios especiales
                    </span>
                    <h2 class="font-display text-lg font-bold text-slate-900 sm:text-2xl">Productos destacados</h2>
                </div>
                <div class="flex items-center gap-1.5 rounded-xl bg-slate-200/70 p-1 sm:w-80" role="tablist">
                    @foreach ([['ofertas', 'Ofertas', $ofertas], ['destacados', 'Más populares', $destacados]] as [$clave, $texto, $lista])
                        @if ($lista->isNotEmpty())
                            <button type="button" role="tab" @click="pestana = '{{ $clave }}'" :aria-selected="pestana === '{{ $clave }}'"
                                    :class="pestana === '{{ $clave }}' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 font-semibold'"
                                    class="flex-1 rounded-lg px-2 py-1.5 text-xs transition">{{ $texto }}</button>
                        @endif
                    @endforeach
                </div>
            </div>

            @foreach ([['ofertas', $ofertas], ['destacados', $destacados]] as [$clave, $lista])
                @if ($lista->isNotEmpty())
                    <div x-show="pestana === '{{ $clave }}'" @if ($clave !== $pestanaInicial) x-cloak @endif role="tabpanel"
                         class="mt-4 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($lista as $producto)
                            <x-producto-card :producto="$producto" />
                        @endforeach
                    </div>
                @endif
            @endforeach
        </section>
    @endif

    {{-- Ayuda por WhatsApp --}}
    <section class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 overflow-hidden rounded-xl bg-linear-to-r from-marca-500 via-blue-700 to-indigo-900 p-5 text-white shadow-lg sm:flex-row sm:items-center sm:p-8">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md">
                <x-icono nombre="herramienta" class="size-7" />
            </span>
            <div class="min-w-0 flex-1">
                <h2 class="font-display text-lg leading-tight font-bold sm:text-xl">¿No sabes qué piezas elegir?</h2>
                <p class="text-sm text-white/80">Cuéntanos qué juegos o programas usas y tu presupuesto, y te recomendamos una configuración.</p>
            </div>
            <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 rounded-lg bg-white px-5 py-2.5 font-display text-sm font-bold text-marca-600 shadow-sm hover:bg-white/90">
                <x-icono nombre="chat" class="size-4.5" /> Chatear por WhatsApp
            </a>
        </div>
    </section>

    {{-- Por qué ForjaPC --}}
    <section class="mx-auto mt-10 max-w-7xl px-4 sm:px-6 lg:px-8">
        <span class="text-[11px] font-bold tracking-wider text-marca-600 uppercase">Por qué confiar en ForjaPC</span>
        <h2 class="font-display text-lg font-bold text-slate-900 sm:text-2xl">Compromiso de alto rendimiento</h2>
        <div class="mt-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-4 lg:gap-4">
            @foreach ([
                ['camion', 'bg-marca-100 text-marca-600', 'Envío a los 22 departamentos', 'Te enviamos el número de guía de la mensajería para que sigas tu paquete hasta tu puerta. También puedes recoger en tienda.'],
                ['escudo', 'bg-emerald-100 text-emerald-700', 'Garantía en cada producto', 'Productos nuevos con la garantía indicada en cada ficha. Te atendemos directamente en Ciudad de Guatemala.'],
                ['tarjeta', 'bg-amber-100 text-amber-800', 'Pago seguro', 'Paga con PayPal, con tarjeta de débito o crédito, o por transferencia bancaria. Los datos de tu tarjeta solo los recibe PayPal.'],
                ['herramienta', 'bg-purple-100 text-purple-800', 'Armado y pruebas en taller', 'Cada PC armada pasa por una lista de verificación y pruebas de encendido, temperatura y estabilidad antes de entregarse.'],
            ] as [$icono, $color, $titulo, $texto])
                <div class="flex items-start gap-3.5 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $color }}"><x-icono :nombre="$icono" class="size-6" /></span>
                    <div>
                        <h3 class="font-display text-sm font-bold text-slate-900">{{ $titulo }}</h3>
                        <p class="mt-0.5 text-xs leading-relaxed text-slate-600">{{ $texto }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
