@use('App\Enums\Rol')
@use('App\Enums\EstadoPedido')
<x-admin-layout title="Inicio">
    @php
        $q = fn ($v) => 'Q'.number_format($v, 2);
        $verVentas = in_array($rol, [Rol::Admin, Rol::Vendedor, Rol::Contador], true);
        $verTaller = in_array($rol, [Rol::Admin, Rol::Tecnico, Rol::Vendedor], true);
        $verOperacion = in_array($rol, [Rol::Admin, Rol::Vendedor], true);
        $maximo = max(1, $tablero['ultimos_dias']->max('total'));
        $total14 = $tablero['ultimos_dias']->sum('total');
        $hora = (int) now()->format('G');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

        $alertas = array_values(array_filter([
            $verOperacion && $tablero['pagos_por_verificar'] ? ['Pagos por verificar', $tablero['pagos_por_verificar'], 'Comprobantes de transferencia por revisar.', 'Verificar', route('admin.pagos.index'), 'tarjeta', 'bg-red-50 text-red-700 ring-red-200'] : null,
            $verOperacion && $tablero['listos_entrega'] ? ['Listos para entregar', $tablero['listos_entrega'], 'Para recoger en tienda o entregar a la mensajería.', 'Ver pedidos', route('admin.pedidos.index', ['estado' => 'LISTO_ENTREGA']), 'camion', 'bg-marca-50 text-marca-700 ring-marca-100'] : null,
            $verTaller && $tablero['ordenes_pendientes'] ? ['Órdenes de armado', $tablero['ordenes_pendientes'], 'Pendientes de tomar o con incidencia.', 'Ir al taller', route('admin.taller.index'), 'herramienta', 'bg-cian-300/30 text-cian-900 ring-cian-300'] : null,
            $verOperacion && $tablero['stock_bajo'] ? ['Stock bajo', $tablero['stock_bajo'], 'Productos por debajo de su mínimo.', 'Ver existencias', route('admin.inventario.index', ['stock_bajo' => 1]), 'paquete', 'bg-amber-50 text-amber-800 ring-amber-200'] : null,
            $verOperacion && $tablero['por_vencer'] ? ['Por vencer', $tablero['por_vencer'], 'Pedidos sin pago que vencen en menos de 2 horas.', 'Revisar', route('admin.pedidos.index', ['estado' => 'PENDIENTE_PAGO']), 'candado', 'bg-slate-100 text-slate-700 ring-slate-200'] : null,
        ]));
    @endphp

    {{-- Encabezado --}}
    <div class="mb-6 flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
        <div>
            <p class="text-[11px] font-bold tracking-wider text-marca-600 uppercase">Panel de {{ mb_strtolower($rol->etiqueta()) }}</p>
            <h2 class="font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $saludo }}, {{ Str::before(Auth::user()->nombre, ' ') }}</h2>
            <p class="text-sm text-slate-500">{{ ucfirst(now()->translatedFormat('l j \d\e F \d\e Y')) }}</p>
        </div>
        @if (isset($tipoCambio) && $tipoCambio)
            <div class="flex items-center gap-3 rounded-xl bg-white px-4 py-2.5 shadow-sm ring-1 ring-slate-200/70">
                <span class="flex size-9 items-center justify-center rounded-lg bg-marca-100 text-marca-600"><x-icono nombre="cambio" class="size-5" /></span>
                <span>
                    <span class="block text-[10px] font-bold tracking-wide text-slate-500 uppercase">Tipo de cambio · {{ $tipoCambio->fuente }}</span>
                    <span class="block font-display font-bold text-slate-900">US$1.00 = Q{{ number_format($tipoCambio->tasa, 5) }}</span>
                </span>
            </div>
        @endif
    </div>

    {{-- Alertas --}}
    @if ($alertas !== [])
        <section class="mb-6 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200/70 sm:p-5">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="flex items-center gap-2 font-display font-bold text-slate-900"><span class="flex size-7 items-center justify-center rounded-lg bg-amber-100 font-bold text-amber-700">!</span> Requiere atención</h3>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800">{{ count($alertas) }} {{ count($alertas) === 1 ? 'alerta' : 'alertas' }}</span>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 {{ [1 => 'xl:grid-cols-1', 2 => 'xl:grid-cols-2', 3 => 'xl:grid-cols-3', 4 => 'xl:grid-cols-4', 5 => 'xl:grid-cols-5'][count($alertas)] }}">
                @foreach ($alertas as [$titulo, $numero, $texto, $accion, $url, $icono, $color])
                    <a href="{{ $url }}" class="group flex flex-col justify-between gap-3 rounded-lg p-3.5 ring-1 transition hover:shadow-md {{ $color }}">
                        <span class="flex items-start justify-between gap-2">
                            <span>
                                <span class="block text-sm font-bold">{{ $titulo }}</span>
                                <span class="block text-xs opacity-80">{{ $texto }}</span>
                            </span>
                            <span class="font-display text-2xl leading-none font-extrabold">{{ $numero }}</span>
                        </span>
                        <span class="flex items-center gap-1 text-xs font-bold"><x-icono :nombre="$icono" class="size-4" /> {{ $accion }} <span class="transition group-hover:translate-x-1">→</span></span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @if ($verVentas)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Ventas de hoy', $q($tablero['ventas_hoy']), 'Cobradas hoy', 'tarjeta', 'bg-emerald-50 text-emerald-600'],
                ['Ventas de la semana', $q($tablero['ventas_semana']), 'Desde el lunes', 'panel', 'bg-marca-100 text-marca-600'],
                ['Ventas del mes', $q($tablero['ventas_mes']), $tablero['pedidos_mes'].' '.($tablero['pedidos_mes'] === 1 ? 'pedido pagado' : 'pedidos pagados'), 'paquete', 'bg-cian-300/30 text-cian-900'],
                ['Ticket promedio', $q($tablero['ticket_promedio']), 'Este mes', 'carrito', 'bg-amber-50 text-amber-600'],
            ] as [$etiqueta, $valor, $detalle, $icono, $color])
                <div class="flex items-start justify-between gap-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <div>
                        <p class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">{{ $etiqueta }}</p>
                        <p class="mt-1 font-display text-2xl font-extrabold tracking-tight text-slate-900">{{ $valor }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $detalle }}</p>
                    </div>
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-lg {{ $color }}"><x-icono :nombre="$icono" class="size-6" /></span>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 xl:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h3 class="font-display font-bold text-slate-900">Ventas de los últimos 14 días</h3>
                        <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($tablero['ultimos_dias']->first()['fecha'])->translatedFormat('j M') }} al {{ \Carbon\Carbon::parse($tablero['ultimos_dias']->last()['fecha'])->translatedFormat('j M') }} · total {{ $q($total14) }}</p>
                    </div>
                    <a href="{{ route('admin.reportes.ventas') }}" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-200">Ver reporte →</a>
                </div>
                <div class="mt-5 flex h-48 items-end gap-1.5" role="img" aria-label="Ventas de los últimos 14 días">
                    @foreach ($tablero['ultimos_dias'] as $dia)
                        @php
                            $esHoy = $loop->last;
                        @endphp
                        <div class="group relative flex h-full flex-1 flex-col items-center justify-end gap-1">
                            <span class="pointer-events-none absolute -top-1 z-10 hidden -translate-y-full rounded bg-marca-800 px-2 py-1 text-[10px] font-semibold whitespace-nowrap text-white group-hover:block">{{ \Carbon\Carbon::parse($dia['fecha'])->translatedFormat('D j') }}: {{ $q($dia['total']) }}</span>
                            <div @class(['w-full rounded-t transition', 'bg-acento-500' => $esHoy, 'bg-marca-500 group-hover:bg-marca-600' => ! $esHoy]) data-alto="{{ max(2, $dia['total'] / $maximo * 100) }}%"></div>
                            <span @class(['text-[10px]', 'font-bold text-acento-600' => $esHoy, 'text-slate-400' => ! $esHoy])>{{ $esHoy ? 'Hoy' : \Carbon\Carbon::parse($dia['fecha'])->format('d') }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="font-display font-bold text-slate-900">Pedidos por estado</h3>
                    <span class="text-xs text-slate-500">{{ collect($tablero['por_estado'])->sum() }} en total</span>
                </div>
                <ul class="flex flex-col gap-1.5 text-sm">
                    @foreach (EstadoPedido::cases() as $estado)
                        <li>
                            <a href="{{ route('admin.pedidos.index', ['estado' => $estado->value]) }}" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 transition hover:bg-slate-100">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $estado->color() }}">{{ $estado->etiqueta() }}</span>
                                <span class="font-display font-bold text-slate-900">{{ $tablero['por_estado'][$estado->value] ?? 0 }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    @endif

    @if ($rol === Rol::Tecnico)
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ([['Órdenes por armar', $tablero['ordenes_pendientes'], 'Pendientes o con incidencia'], ['En armado o en pruebas', $tablero['ordenes_en_proceso'], 'Trabajo en curso en el taller']] as [$titulo, $numero, $texto])
                <a href="{{ route('admin.taller.index') }}" class="flex items-center justify-between gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md">
                    <span>
                        <span class="block text-[11px] font-bold tracking-wide text-slate-500 uppercase">{{ $titulo }}</span>
                        <span class="block font-display text-4xl font-extrabold text-slate-900">{{ $numero }}</span>
                        <span class="block text-xs text-slate-500">{{ $texto }}</span>
                    </span>
                    <span class="flex size-12 items-center justify-center rounded-xl bg-marca-800 text-cian-400"><x-icono nombre="herramienta" class="size-6" /></span>
                </a>
            @endforeach
        </div>
    @endif

    @if ($rol === Rol::Contador)
        <section class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Libro diario', 'admin.reportes.libro-diario', 'lista'], ['Libro mayor', 'admin.reportes.libro-mayor', 'lista'], ['Balance de comprobación', 'admin.reportes.balance', 'cambio'], ['Estado de resultados', 'admin.reportes.resultados', 'tarjeta']] as [$texto, $ruta, $icono])
                <a href="{{ route($ruta) }}" class="flex items-center gap-3 rounded-xl bg-white p-5 font-display font-semibold text-slate-900 shadow-sm ring-1 ring-slate-200/70 transition hover:shadow-md hover:ring-marca-500">
                    <span class="flex size-10 items-center justify-center rounded-lg bg-marca-100 text-marca-600"><x-icono :nombre="$icono" class="size-5" /></span> {{ $texto }}
                </a>
            @endforeach
        </section>
    @endif

    @if ($rol === Rol::Admin)
        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200/70 xl:col-span-2">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="font-display font-bold text-slate-900">Actividad reciente</h3>
                    <a href="{{ route('admin.bitacora.index') }}" class="text-xs font-semibold text-marca-600 hover:underline">Ver bitácora</a>
                </div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($actividadReciente as $registro)
                        <li class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                            <span class="flex items-center gap-3">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{ mb_strtoupper(mb_substr($registro->usuario?->nombre ?? 'S', 0, 1)) }}</span>
                                <span>
                                    <span class="font-medium text-slate-800">{{ \App\Models\BitacoraAuditoria::accionLegible($registro->accion) }}</span>
                                    @if ($registro->entidad_tipo)<span class="text-slate-500"> · {{ $registro->entidadLegible() }} #{{ $registro->entidad_id }}</span>@endif
                                    <span class="block text-xs text-slate-500">{{ $registro->usuario?->nombre ?? 'Sistema' }}</span>
                                </span>
                            </span>
                            <time class="shrink-0 text-xs text-slate-400">{{ $registro->created_at->diffForHumans() }}</time>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-sm text-slate-500">Sin actividad registrada todavía.</li>
                    @endforelse
                </ul>
            </section>

            <section class="flex flex-col gap-3 rounded-xl bg-marca-800 p-5 text-sm text-marca-300 shadow-sm">
                <h3 class="font-display font-bold text-white">Tienda</h3>
                <div class="flex items-center justify-between rounded-lg bg-white/5 px-3 py-2.5"><span>Clientes registrados</span><span class="font-display text-lg font-bold text-white">{{ $totalClientes }}</span></div>
                <div class="flex items-center justify-between rounded-lg bg-white/5 px-3 py-2.5"><span>Nuevos este mes</span><span class="font-display text-lg font-bold text-cian-400">+{{ $clientesNuevosMes }}</span></div>
                <div class="rounded-lg bg-white/5 px-3 py-2.5">
                    <span class="block">Tipo de cambio</span>
                    <span class="font-display text-lg font-bold text-white">{{ $tipoCambio ? 'Q'.number_format($tipoCambio->tasa, 5) : 'Sin registrar' }}</span>
                    @if ($tipoCambio)<span class="block text-xs">Del {{ $tipoCambio->fecha->format('d/m/Y') }} · {{ $tipoCambio->fuente }}</span>@endif
                </div>
                <a href="{{ route('inicio') }}" class="mt-auto rounded-lg bg-white/10 px-3 py-2 text-center font-semibold text-white hover:bg-white/20">Ver la tienda →</a>
            </section>
        </div>
    @endif
</x-admin-layout>
