<x-app-layout title="Mi cuenta">
    @php
        $activos = $user->pedidos()->whereNotIn('estado', ['ENTREGADO', 'CANCELADO', 'REEMBOLSADO'])->latest('id')->get(['id', 'numero']);
        $recientes = $user->pedidos()->withCount('items')->with('items:id,pedido_id,descripcion,nombre_configuracion,grupo_configuracion')->latest('id')->take(3)->get();
        $configuraciones = $user->configuraciones()->count();
        $direcciones = $user->direcciones()->count();
    @endphp

    <div class="mx-auto grid max-w-7xl items-start gap-6 px-4 py-6 sm:px-6 lg:grid-cols-12 lg:px-8">
        <div class="lg:col-span-3">
            @include('cuenta.partials.menu')
        </div>

        <section class="flex flex-col gap-8 lg:col-span-9">
            <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">¡Hola, {{ Str::before($user->nombre, ' ') }}!</h1>
                    <p class="mt-1 text-sm text-slate-600">Aquí sigues tus pedidos, tus configuraciones y tus direcciones de entrega.</p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <a href="{{ route('tienda.configurador') }}" class="flex items-center gap-1.5 rounded-lg bg-white px-4 py-2.5 font-display text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50"><x-icono nombre="herramienta" class="size-4.5" /> Arma tu PC</a>
                    <a href="{{ route('tienda.buscar') }}" class="flex items-center gap-1.5 rounded-lg bg-marca-600 px-4 py-2.5 font-display text-sm font-semibold text-white shadow-sm hover:bg-marca-700"><x-icono nombre="carrito" class="size-4.5" /> Seguir comprando</a>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['Pedidos en curso', 'paquete', 'bg-marca-100 text-marca-700', $activos->count(), $activos->count() === 1 ? 'en curso' : 'en curso', $activos->first() ? [$activos->first()->numero, route('pedidos.show', $activos->first())] : null],
                    ['Configuraciones', 'cpu', 'bg-cian-300/40 text-cian-900', $configuraciones, $configuraciones === 1 ? 'guardada' : 'guardadas', [$configuraciones ? 'Ver mis PCs' : 'Arma la primera', $configuraciones ? route('configuraciones.index') : route('tienda.configurador')]],
                    ['Direcciones', 'camion', 'bg-slate-200 text-slate-800', $direcciones, $direcciones === 1 ? 'dirección' : 'direcciones', [$direcciones ? 'Administrar' : 'Agregar una', $direcciones ? route('direcciones.index') : route('direcciones.create')]],
                ] as [$titulo, $icono, $color, $numero, $unidad, $enlace])
                    <div class="flex flex-col justify-between gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold tracking-wider text-slate-500 uppercase">{{ $titulo }}</span>
                            <span class="flex size-8 items-center justify-center rounded-lg {{ $color }}"><x-icono :nombre="$icono" class="size-4.5" /></span>
                        </div>
                        <div>
                            <div class="flex items-baseline gap-1.5"><span class="font-display text-3xl font-extrabold text-slate-900">{{ $numero }}</span><span class="text-sm text-slate-500">{{ $unidad }}</span></div>
                            @if ($enlace)
                                <a href="{{ $enlace[1] }}" class="mt-1 inline-flex items-center gap-1 text-xs font-bold text-marca-600 hover:underline">{{ $enlace[0] }} <x-icono nombre="chevron-derecha" class="size-3.5" /></a>
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="flex flex-col justify-between gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold tracking-wider text-slate-500 uppercase">NIT registrado</span>
                        <span class="flex size-8 items-center justify-center rounded-lg bg-slate-200 text-marca-600"><x-icono nombre="lista" class="size-4.5" /></span>
                    </div>
                    <div>
                        <span class="font-display text-xl font-bold tracking-tight text-slate-900">{{ $user->nit }}</span>
                        <a href="{{ route('profile.edit') }}" class="mt-1 flex items-center gap-1 text-xs font-bold text-marca-600 hover:underline">Cambiar mis datos <x-icono nombre="chevron-derecha" class="size-3.5" /></a>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-display text-lg font-bold text-slate-900"><x-icono nombre="paquete" class="size-5.5 text-marca-600" /> Pedidos recientes</h2>
                    @if ($recientes->isNotEmpty())
                        <a href="{{ route('pedidos.index') }}" class="flex items-center gap-1 text-sm font-bold text-marca-600 hover:underline">Ver todos <x-icono nombre="chevron-derecha" class="size-4" /></a>
                    @endif
                </div>

                @forelse ($recientes as $pedido)
                    @php
                        $pc = $pedido->items->firstWhere('grupo_configuracion', '!=', null);
                        $titulo = $pc ? $pc->nombre_configuracion : $pedido->items->first()?->descripcion;
                        $resto = $pedido->items_count - ($pc ? $pedido->items->where('grupo_configuracion', $pc->grupo_configuracion)->count() : 1);
                    @endphp
                    <article class="flex flex-col justify-between gap-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md lg:flex-row lg:items-center">
                        <div class="flex min-w-0 flex-1 items-center gap-4">
                            <span class="flex size-14 shrink-0 items-center justify-center rounded-lg bg-slate-100"><x-icono :nombre="$pc ? 'monitor' : 'paquete'" class="size-7 text-slate-700" /></span>
                            <div class="flex min-w-0 flex-col">
                                <div class="flex flex-wrap items-center gap-2 text-xs">
                                    <span class="font-display font-extrabold text-slate-900">{{ $pedido->numero }}</span>
                                    <span class="text-slate-500">· {{ $pedido->created_at->translatedFormat('j M Y') }}</span>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold uppercase {{ $pedido->estado->color() }}">{{ $pedido->estado->etiqueta() }}</span>
                                </div>
                                <h3 class="mt-1 truncate font-display font-semibold text-slate-900">{{ $titulo }}{{ $resto > 0 ? ' + '.$resto.' '.($resto === 1 ? 'artículo' : 'artículos') : '' }}</h3>
                                <p class="text-xs text-slate-500">{{ $pedido->esEnvio() ? 'Envío a domicilio' : 'Recoger en tienda' }} · {{ $pedido->metodo_pago->etiqueta() }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center justify-between gap-4 lg:justify-end">
                            <div class="flex flex-col lg:items-end">
                                <span class="text-[11px] font-bold text-slate-500 uppercase">Total</span>
                                <span class="font-display text-lg font-extrabold text-slate-900">Q{{ number_format($pedido->total, 2) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('pedidos.show', $pedido) }}" class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-200">Ver detalle</a>
                                @if ($pedido->estaPendienteDePago())
                                    <a href="{{ route('pedidos.show', $pedido) }}" class="rounded-lg bg-marca-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-marca-700">Pagar ahora</a>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="flex flex-col items-center gap-3 rounded-xl bg-white px-6 py-10 text-center shadow-sm ring-1 ring-slate-200/70">
                        <x-icono nombre="paquete" class="size-10 text-slate-300" />
                        <p class="font-display font-bold text-slate-900">Todavía no tienes pedidos</p>
                        <p class="text-sm text-slate-500">Cuando compres, aquí verás el estado de cada pedido.</p>
                        <a href="{{ route('inicio') }}" class="rounded-lg bg-marca-600 px-5 py-2 text-sm font-bold text-white hover:bg-marca-700">Ir a la tienda</a>
                    </div>
                @endforelse
            </div>

            <section class="flex flex-col items-start justify-between gap-4 overflow-hidden rounded-xl bg-marca-800 p-6 text-white shadow-lg sm:flex-row sm:items-center">
                <div>
                    <h2 class="font-display text-lg font-bold">¿Listo para tu próxima PC?</h2>
                    <p class="text-sm text-marca-300">Arma tu configuración y nosotros validamos que todo sea compatible.</p>
                </div>
                <a href="{{ route('tienda.configurador') }}" class="shrink-0 rounded-lg bg-linear-to-r from-acento-500 to-acento-600 px-5 py-2.5 font-display text-sm font-bold shadow-lg shadow-orange-500/30 hover:to-acento-700">Arma tu PC</a>
            </section>
        </section>
    </div>
</x-app-layout>
