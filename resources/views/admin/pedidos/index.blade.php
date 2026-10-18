@use('App\Enums\MetodoPago')
<x-admin-layout title="Pedidos">
    @php
        $filtros = request()->only(['q', 'desde', 'hasta', 'pago', 'entrega']);
        $hayFiltros = array_filter($filtros) !== [] || request()->filled('estado');
    @endphp

    <div class="mb-5 flex flex-col justify-between gap-3 md:flex-row md:items-end">
        <div>
            <p class="text-[11px] font-bold tracking-wider text-marca-600 uppercase">Ventas</p>
            <h2 class="font-display text-2xl font-bold tracking-tight text-slate-900">Gestión de pedidos</h2>
            <p class="text-sm text-slate-500">Pedidos de clientes, pagos, armado, envíos y entregas.</p>
        </div>
    </div>

    <div class="mb-5 grid grid-cols-2 gap-3 xl:grid-cols-4">
        @foreach ([
            ['PENDIENTE_PAGO', 'Por cobrar', 'candado', 'bg-amber-50 text-amber-700'],
            ['EN_ARMADO', 'En el taller', 'herramienta', 'bg-cian-300/30 text-cian-900'],
            ['LISTO_ENTREGA', 'Listos para entregar', 'tienda', 'bg-marca-100 text-marca-600'],
            ['ENVIADO', 'En camino', 'camion', 'bg-slate-100 text-slate-700'],
        ] as [$estado, $titulo, $icono, $color])
            <a href="{{ route('admin.pedidos.index', [...$filtros, 'estado' => $estado]) }}" class="flex items-center justify-between gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md">
                <span>
                    <span class="block text-[11px] font-bold tracking-wide text-slate-500 uppercase">{{ $titulo }}</span>
                    <span class="mt-1 block font-display text-2xl font-extrabold text-slate-900">{{ $conteos[$estado] ?? 0 }}</span>
                </span>
                <span class="flex size-11 shrink-0 items-center justify-center rounded-lg {{ $color }}"><x-icono :nombre="$icono" class="size-6" /></span>
            </a>
        @endforeach
    </div>

    <form method="GET" class="mb-5 flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70">
        <input type="hidden" name="estado" value="{{ request('estado') }}">
        <div class="grid grid-cols-1 gap-2 lg:grid-cols-12">
            <label class="relative lg:col-span-4">
                <span class="sr-only">Buscar</span>
                <x-icono nombre="buscar" class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                <input name="q" value="{{ request('q') }}" placeholder="Número, cliente o correo" class="w-full rounded-lg border-0 bg-slate-100 py-2.5 pl-10 text-sm focus:bg-white focus:ring-2 focus:ring-marca-600">
            </label>
            <label class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 lg:col-span-2">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Desde</span>
                <input type="date" name="desde" value="{{ request('desde') }}" class="w-full border-0 bg-transparent py-2 text-sm focus:ring-0">
            </label>
            <label class="flex items-center gap-2 rounded-lg bg-slate-100 px-3 lg:col-span-2">
                <span class="text-[10px] font-bold text-slate-500 uppercase">Hasta</span>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="w-full border-0 bg-transparent py-2 text-sm focus:ring-0">
            </label>
            <select name="entrega" aria-label="Entrega" class="rounded-lg border-0 bg-slate-100 py-2.5 text-sm focus:ring-2 focus:ring-marca-600 lg:col-span-2">
                <option value="">Entrega: todas</option>
                <option value="ENVIO" @selected(request('entrega') === 'ENVIO')>Envío a domicilio</option>
                <option value="RECOGER" @selected(request('entrega') === 'RECOGER')>Recoger en tienda</option>
            </select>
            <select name="pago" aria-label="Forma de pago" class="rounded-lg border-0 bg-slate-100 py-2.5 text-sm focus:ring-2 focus:ring-marca-600 lg:col-span-2">
                <option value="">Pago: todos</option>
                @foreach (MetodoPago::cases() as $metodo)
                    <option value="{{ $metodo->value }}" @selected(request('pago') === $metodo->value)>{{ $metodo->etiqueta() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col items-start justify-between gap-2 xl:flex-row xl:items-center">
            <div class="flex w-full gap-1.5 overflow-x-auto pb-1 xl:w-auto xl:pb-0">
                <a href="{{ route('admin.pedidos.index', $filtros) }}" @class(['shrink-0 rounded-full px-3 py-1.5 text-xs font-bold', 'bg-marca-800 text-white' => ! request('estado'), 'bg-slate-100 text-slate-700 hover:bg-slate-200' => request('estado')])>Todos <span class="opacity-70">({{ collect($conteos)->sum() }})</span></a>
                @foreach ($estados as $estado)
                    <a href="{{ route('admin.pedidos.index', [...$filtros, 'estado' => $estado->value]) }}" @class([
                        'shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold',
                        'bg-marca-800 text-white' => request('estado') === $estado->value,
                        'bg-slate-100 text-slate-700 hover:bg-slate-200' => request('estado') !== $estado->value,
                    ])>{{ $estado->etiqueta() }} <span class="font-bold opacity-70">({{ $conteos[$estado->value] ?? 0 }})</span></a>
                @endforeach
            </div>
            <div class="flex shrink-0 items-center gap-2 self-end xl:self-auto">
                @if ($hayFiltros)
                    <a href="{{ route('admin.pedidos.index') }}" class="px-3 py-1.5 text-xs font-semibold tracking-wide text-slate-500 uppercase hover:text-slate-900">Limpiar filtros</a>
                @endif
                <button type="submit" class="flex items-center gap-1.5 rounded-lg bg-marca-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-marca-700"><x-icono nombre="ajustes" class="size-4" /> Aplicar filtros</button>
            </div>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-slate-200/70">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-[11px] font-bold tracking-wider text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3">Pedido / fecha</th>
                        <th class="px-3 py-3">Cliente</th>
                        <th class="px-3 py-3">Entrega / artículos</th>
                        <th class="px-3 py-3 text-right">Total</th>
                        <th class="px-3 py-3">Pago</th>
                        <th class="px-3 py-3">Estado</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pedidos as $pedido)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-4 py-3.5">
                                <a href="{{ route('admin.pedidos.show', $pedido) }}" class="font-display font-bold text-marca-700 hover:underline">{{ $pedido->numero }}</a>
                                <span class="block text-xs text-slate-500">{{ $pedido->created_at->format('d/m/Y H:i') }}</span>
                            </td>
                            <td class="px-3 py-3.5">
                                <span class="block font-medium text-slate-900">{{ $pedido->usuario->nombre }}</span>
                                <span class="block text-xs text-slate-500">{{ $pedido->usuario->email }} · NIT {{ $pedido->nit }}</span>
                            </td>
                            <td class="px-3 py-3.5 text-xs text-slate-600">
                                <span class="flex items-center gap-1"><x-icono :nombre="$pedido->esEnvio() ? 'camion' : 'tienda'" class="size-4 text-slate-400" /> {{ $pedido->esEnvio() ? 'Envío' : 'Recoger' }}</span>
                                <span>{{ $pedido->items_count }} {{ $pedido->items_count === 1 ? 'artículo' : 'artículos' }}{{ $pedido->costo_armado > 0 ? ' · con armado' : '' }}</span>
                            </td>
                            <td class="px-3 py-3.5 text-right font-display font-bold whitespace-nowrap text-slate-900">Q{{ number_format($pedido->total, 2) }}</td>
                            <td class="px-3 py-3.5 text-xs text-slate-600">{{ $pedido->metodo_pago->etiqueta() }}</td>
                            <td class="px-3 py-3.5"><span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold whitespace-nowrap {{ $pedido->estado->color() }}">{{ $pedido->estado->etiqueta() }}</span></td>
                            <td class="px-4 py-3.5 text-center">
                                <a href="{{ route('admin.pedidos.show', $pedido) }}" @class([
                                    'inline-block rounded-lg px-3 py-1.5 text-xs font-semibold transition',
                                    'bg-marca-600 text-white shadow-sm hover:bg-marca-700' => in_array($pedido->estado->value, ['PAGADO', 'EN_PREPARACION', 'LISTO_ENTREGA'], true),
                                    'bg-slate-100 text-slate-800 hover:bg-slate-200' => ! in_array($pedido->estado->value, ['PAGADO', 'EN_PREPARACION', 'LISTO_ENTREGA'], true),
                                ])>Gestionar</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-12 text-center text-slate-500">No hay pedidos con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $pedidos->links() }}</div>
</x-admin-layout>
