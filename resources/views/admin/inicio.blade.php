@use('App\Enums\Rol')
@use('App\Enums\EstadoPedido')
<x-admin-layout title="Inicio">
    @php
        $q = fn ($v) => 'Q'.number_format($v, 2);
        $verVentas = in_array($rol, [Rol::Admin, Rol::Vendedor, Rol::Contador], true);
        $verTaller = in_array($rol, [Rol::Admin, Rol::Tecnico, Rol::Vendedor], true);
        $maximo = max(1, $tablero['ultimos_dias']->max('total'));
    @endphp

    <p class="mb-6 text-slate-600">Hola, <strong>{{ Str::before(Auth::user()->nombre, ' ') }}</strong>. Hoy es {{ now()->translatedFormat('l j \d\e F') }}.</p>

    {{-- Alertas y tareas pendientes --}}
    @php
        $alertas = array_filter([
            in_array($rol, [Rol::Admin, Rol::Vendedor], true) && $tablero['pagos_por_verificar'] ? [$tablero['pagos_por_verificar'].' pago(s) por verificar', route('admin.pagos.index'), 'tarjeta'] : null,
            in_array($rol, [Rol::Admin, Rol::Vendedor], true) && $tablero['listos_entrega'] ? [$tablero['listos_entrega'].' pedido(s) listos para entregar o enviar', route('admin.pedidos.index', ['estado' => 'LISTO_ENTREGA']), 'camion'] : null,
            $verTaller && $tablero['ordenes_pendientes'] ? [$tablero['ordenes_pendientes'].' orden(es) de armado pendientes o con incidencia', route('admin.taller.index'), 'herramienta'] : null,
            in_array($rol, [Rol::Admin, Rol::Vendedor], true) && $tablero['stock_bajo'] ? [$tablero['stock_bajo'].' producto(s) con stock bajo', route('admin.inventario.index', ['stock_bajo' => 1]), 'paquete'] : null,
            in_array($rol, [Rol::Admin, Rol::Vendedor], true) && $tablero['por_vencer'] ? [$tablero['por_vencer'].' pedido(s) vencen en menos de 2 horas', route('admin.pedidos.index', ['estado' => 'PENDIENTE_PAGO']), 'candado'] : null,
        ]);
    @endphp
    @if ($alertas !== [])
        <div class="mb-6 grid gap-3 md:grid-cols-2">
            @foreach ($alertas as [$texto, $url, $icono])
                <a href="{{ $url }}" class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900 hover:bg-amber-100">
                    <x-icono :nombre="$icono" class="size-5 shrink-0" /> {{ $texto }} <span class="ml-auto">&rarr;</span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($verVentas)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Ventas de hoy', $q($tablero['ventas_hoy']), 'cobradas', 'tarjeta', 'text-emerald-600 bg-emerald-50'],
                ['Ventas de la semana', $q($tablero['ventas_semana']), 'desde el lunes', 'panel', 'text-marca-600 bg-marca-50'],
                ['Ventas del mes', $q($tablero['ventas_mes']), $tablero['pedidos_mes'].' pedido(s) pagados', 'paquete', 'text-sky-600 bg-sky-50'],
                ['Ticket promedio', $q($tablero['ticket_promedio']), 'este mes', 'carrito', 'text-amber-600 bg-amber-50'],
            ] as [$etiqueta, $valor, $detalle, $icono, $color])
                <div class="flex items-start gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <span class="rounded-lg p-2.5 {{ $color }}"><x-icono :nombre="$icono" class="size-6" /></span>
                    <div>
                        <p class="text-sm text-slate-500">{{ $etiqueta }}</p>
                        <p class="text-2xl font-extrabold text-slate-900">{{ $valor }}</p>
                        <p class="text-xs text-slate-500">{{ $detalle }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 xl:col-span-2">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Ventas de los últimos 14 días</h2>
                    <a href="{{ route('admin.reportes.ventas') }}" class="text-xs font-semibold text-marca-600 hover:underline">Ver reporte</a>
                </div>
                <div class="mt-4 flex h-40 items-end gap-1.5" role="img" aria-label="Ventas de los últimos 14 días">
                    @foreach ($tablero['ultimos_dias'] as $dia)
                        <div class="group flex flex-1 flex-col items-center justify-end gap-1" title="{{ \Carbon\Carbon::parse($dia['fecha'])->translatedFormat('D j') }}: {{ $q($dia['total']) }}">
                            <div class="w-full rounded-t bg-marca-500 group-hover:bg-acento-500" style="height: {{ max(2, $dia['total'] / $maximo * 100) }}%"></div>
                            <span class="text-[10px] text-slate-400">{{ \Carbon\Carbon::parse($dia['fecha'])->format('d') }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Pedidos por estado</h2>
                <ul class="mt-3 space-y-1.5 text-sm">
                    @foreach (EstadoPedido::cases() as $estado)
                        <li>
                            <a href="{{ route('admin.pedidos.index', ['estado' => $estado->value]) }}" class="flex items-center justify-between rounded-lg px-2 py-1 hover:bg-slate-50">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $estado->color() }}">{{ $estado->etiqueta() }}</span>
                                <span class="font-semibold">{{ $tablero['por_estado'][$estado->value] ?? 0 }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    @endif

    @if ($rol === Rol::Tecnico)
        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('admin.taller.index') }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-marca-400">
                <p class="text-sm text-slate-500">Órdenes por armar</p>
                <p class="text-4xl font-extrabold text-slate-900">{{ $tablero['ordenes_pendientes'] }}</p>
            </a>
            <a href="{{ route('admin.taller.index') }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-marca-400">
                <p class="text-sm text-slate-500">Órdenes en armado o en pruebas</p>
                <p class="text-4xl font-extrabold text-slate-900">{{ $tablero['ordenes_en_proceso'] }}</p>
            </a>
        </div>
    @endif

    @if ($rol === Rol::Contador)
        <section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Libro diario', 'admin.reportes.libro-diario'], ['Libro mayor', 'admin.reportes.libro-mayor'], ['Balance de comprobación', 'admin.reportes.balance'], ['Estado de resultados', 'admin.reportes.resultados']] as [$texto, $ruta])
                <a href="{{ route($ruta) }}" class="rounded-xl bg-white p-5 font-semibold text-marca-700 shadow-sm ring-1 ring-slate-200 hover:ring-marca-400">{{ $texto }} &rarr;</a>
            @endforeach
        </section>
    @endif

    @if ($rol === Rol::Admin)
        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 xl:col-span-2">
                <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Actividad reciente</h2>
                <ul class="divide-y divide-slate-100">
                    @forelse ($actividadReciente as $registro)
                        <li class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                            <span>
                                <span class="font-medium text-slate-800">{{ Str::of($registro->accion)->replace('_', ' ')->ucfirst() }}</span>
                                @if ($registro->entidad_tipo)<span class="text-slate-500">· {{ $registro->entidad_tipo }} #{{ $registro->entidad_id }}</span>@endif
                                <span class="block text-xs text-slate-500">{{ $registro->usuario?->nombre ?? 'Sistema' }}</span>
                            </span>
                            <time class="shrink-0 text-xs text-slate-400">{{ $registro->created_at->diffForHumans() }}</time>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-500">Sin actividad registrada todavía.</li>
                    @endforelse
                </ul>
            </section>

            <section class="space-y-3 rounded-xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Tienda</h2>
                <div class="flex justify-between"><span class="text-slate-500">Clientes registrados</span><span class="font-semibold">{{ $totalClientes }} <span class="text-xs font-normal text-emerald-700">+{{ $clientesNuevosMes }} este mes</span></span></div>
                <div class="flex justify-between"><span class="text-slate-500">Tipo de cambio</span><span class="font-semibold">{{ $tipoCambio ? 'Q'.number_format($tipoCambio->tasa, 5) : 'Sin registrar' }}</span></div>
                @if ($tipoCambio)<p class="text-xs text-slate-500">Del {{ $tipoCambio->fecha->format('d/m/Y') }} · fuente {{ $tipoCambio->fuente }}</p>@endif
            </section>
        </div>
    @endif
</x-admin-layout>
