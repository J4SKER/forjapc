@php
    use App\Enums\EstadoOrdenArmado;
    use App\Enums\EstadoPago;
    use App\Enums\EstadoPedido;
    use App\Enums\MetodoPago;
    use App\Enums\Rol;

    $usuario = Auth::user();
    $rolVisto = $usuario->rolEfectivo();

    $todos = [Rol::Admin, Rol::Vendedor, Rol::Tecnico, Rol::Contador];
    $ventas = [Rol::Admin, Rol::Vendedor];

    // Contadores de trabajo pendiente para el menú (consultas simples, solo conteos).
    $contadores = [
        'pedidos' => \App\Models\Pedido::query()->where('estado', EstadoPedido::PendientePago)->count(),
        'pagos' => \App\Models\Pago::query()->where('estado', EstadoPago::Pendiente)->where('metodo', MetodoPago::Transferencia)->whereNotNull('comprobante_ruta')->count(),
        'taller' => \App\Models\OrdenArmado::query()->whereIn('estado', [EstadoOrdenArmado::Pendiente, EstadoOrdenArmado::Incidencia])->count(),
        'stock' => (int) DB::table('v_existencias')->where('stock_bajo', 1)->count(),
    ];

    // Menú por grupos: [nombre, icono, ruta, patrón activo, roles, contador, estilo del contador].
    $grupos = [
        '' => [
            ['Inicio', 'panel', 'admin.inicio', 'admin.inicio', $todos, null, null],
        ],
        'Ventas' => [
            ['Pedidos', 'paquete', 'admin.pedidos.index', 'admin.pedidos.*', [...$ventas, Rol::Contador], $contadores['pedidos'], 'bg-marca-500 text-white'],
            ['Pagos por verificar', 'tarjeta', 'admin.pagos.index', 'admin.pagos.*', [...$ventas, Rol::Contador], $contadores['pagos'], 'bg-red-500 text-white'],
        ],
        'Taller' => [
            ['Órdenes de armado', 'herramienta', 'admin.taller.index', 'admin.taller.*', [Rol::Admin, Rol::Tecnico, Rol::Vendedor], $contadores['taller'], 'bg-cian-300 text-cian-900'],
        ],
        'Catálogo' => [
            ['Productos', 'tienda', 'admin.productos.index', 'admin.productos.*', $ventas, null, null],
            ['Categorías', 'lista', 'admin.categorias.index', 'admin.categorias.*', $ventas, null, null],
            ['Marcas', 'escudo', 'admin.marcas.index', 'admin.marcas.*', $ventas, null, null],
            ['Reseñas', 'chat', 'admin.resenas.index', 'admin.resenas.*', $ventas, null, null],
        ],
        'Inventario' => [
            ['Existencias y kardex', 'paquete', 'admin.inventario.index', 'admin.inventario.*', [...$ventas, Rol::Contador], $contadores['stock'] ? 'Bajo' : null, 'bg-red-100 text-red-700'],
            ['Compras', 'carrito', 'admin.compras.index', 'admin.compras.*', [...$ventas, Rol::Contador], null, null],
            ['Proveedores', 'camion', 'admin.proveedores.index', 'admin.proveedores.*', $ventas, null, null],
        ],
        'Contabilidad' => [
            ['Partidas', 'lista', 'admin.contabilidad.partidas.index', 'admin.contabilidad.partidas.*', [Rol::Admin, Rol::Contador], null, null],
            ['Períodos', 'candado', 'admin.contabilidad.periodos.index', 'admin.contabilidad.periodos.*', [Rol::Admin, Rol::Contador], null, null],
            ['Nomenclatura', 'escudo', 'admin.contabilidad.cuentas.index', 'admin.contabilidad.cuentas.*', [Rol::Admin, Rol::Contador], null, null],
        ],
        'Reportes' => [
            ['Ventas', 'panel', 'admin.reportes.ventas', 'admin.reportes.ventas', [...$ventas, Rol::Contador], null, null],
            ['Valorización de inventario', 'paquete', 'admin.reportes.inventario', 'admin.reportes.inventario', [...$ventas, Rol::Contador], null, null],
            ['Libro diario', 'lista', 'admin.reportes.libro-diario', 'admin.reportes.libro-diario', [Rol::Admin, Rol::Contador], null, null],
            ['Libro mayor', 'lista', 'admin.reportes.libro-mayor', 'admin.reportes.libro-mayor', [Rol::Admin, Rol::Contador], null, null],
            ['Balance de comprobación', 'cambio', 'admin.reportes.balance', 'admin.reportes.balance', [Rol::Admin, Rol::Contador], null, null],
            ['Estado de resultados', 'tarjeta', 'admin.reportes.resultados', 'admin.reportes.resultados', [Rol::Admin, Rol::Contador], null, null],
        ],
        'Sistema' => [
            ['Usuarios', 'usuarios', 'admin.usuarios.index', 'admin.usuarios.*', [Rol::Admin], null, null],
            ['Parámetros', 'ajustes', 'admin.parametros.index', 'admin.parametros.*', [Rol::Admin], null, null],
            ['Bitácora', 'candado', 'admin.bitacora.index', 'admin.bitacora.*', [Rol::Admin], null, null],
        ],
    ];
    $iniciales = collect(explode(' ', $usuario->nombre))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex">

        <title>{{ isset($title) ? $title.' · ' : '' }}Panel ForjaPC</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|plus-jakarta-sans:600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-superficie font-sans text-slate-800 antialiased" x-data="{ menu: false }">
        {{-- Barra lateral --}}
        <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="menu = false"></div>
        <aside class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col justify-between overflow-y-auto bg-marca-800 text-marca-300 transition-transform [scrollbar-width:thin] lg:translate-x-0"
               :class="menu && 'translate-x-0'">
            <div class="flex flex-col">
                <a href="{{ route('admin.inicio') }}" class="flex items-center gap-3 px-5 py-4">
                    <x-application-logo class="size-9" />
                    <span>
                        <span class="block font-display text-lg leading-none font-bold text-white">ForjaPC <span class="text-cian-400">Panel</span></span>
                        <span class="mt-1 block text-[10px] font-bold tracking-wider text-marca-300 uppercase">Administración</span>
                    </span>
                </a>

                <div class="mx-4 mb-3 flex items-center gap-3 rounded-xl bg-white/5 p-2.5">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-marca-500 text-xs font-bold text-white">{{ $iniciales }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-white">{{ $usuario->nombre }}</span>
                        <span class="block truncate text-[11px] text-cian-400">{{ $usuario->rol->etiqueta() }}{{ $usuario->estaEnVistaPrevia() ? ' · viendo como '.$rolVisto->etiqueta() : '' }}</span>
                    </span>
                </div>

                @if ($usuario->rol === Rol::Admin)
                    <div class="mx-4 mb-4">
                        <p class="mb-1 text-[10px] font-bold tracking-wider text-marca-300 uppercase">Ver el panel como</p>
                        <div class="grid grid-cols-4 gap-1 rounded-lg bg-white/10 p-1">
                            <form method="POST" action="{{ route('admin.vista-previa.destroy') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" @class(['w-full rounded py-1 text-[11px] font-semibold transition', 'bg-marca-500 text-white' => ! $usuario->estaEnVistaPrevia(), 'hover:text-white' => $usuario->estaEnVistaPrevia()])>Admin</button>
                            </form>
                            @foreach ([[Rol::Vendedor, 'Ventas'], [Rol::Tecnico, 'Técnico'], [Rol::Contador, 'Conta']] as [$rolPrevia, $corto])
                                <form method="POST" action="{{ route('admin.vista-previa.store') }}">
                                    @csrf
                                    <input type="hidden" name="rol" value="{{ $rolPrevia->value }}">
                                    <button type="submit" title="Ver como {{ $rolPrevia->etiqueta() }}" @class(['w-full rounded py-1 text-[11px] font-semibold transition', 'bg-marca-500 text-white' => $usuario->estaEnVistaPrevia() && $rolVisto === $rolPrevia, 'hover:text-white' => ! ($usuario->estaEnVistaPrevia() && $rolVisto === $rolPrevia)])>{{ $corto }}</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @endif

                <nav class="flex flex-col gap-4 px-4 pb-6" aria-label="Panel administrativo">
                    @foreach ($grupos as $grupo => $secciones)
                        @php
                            $visibles = array_filter($secciones, fn ($s) => $usuario->tieneRolEfectivo(...$s[4]));
                        @endphp
                        @continue($visibles === [])
                        <div class="flex flex-col gap-0.5">
                            @if ($grupo !== '')
                                <p class="mb-1 px-2.5 text-[10px] font-bold tracking-wider text-marca-300/80 uppercase">{{ $grupo }}</p>
                            @endif
                            @foreach ($visibles as [$nombre, $icono, $ruta, $patron, $roles, $contador, $estilo])
                                @php
                                    $activa = request()->routeIs($patron);
                                @endphp
                                <a href="{{ route($ruta) }}" @if ($activa) aria-current="page" @endif @class([
                                    'flex items-center justify-between gap-2 rounded-lg px-2.5 py-2 text-sm transition',
                                    'bg-marca-500 font-semibold text-white' => $activa,
                                    'hover:bg-white/10 hover:text-white' => ! $activa,
                                ])>
                                    <span class="flex items-center gap-3"><x-icono :nombre="$icono" class="size-4.5" /> {{ $nombre }}</span>
                                    @if ($contador)
                                        <span class="rounded-full px-1.5 py-0.5 text-[10px] font-bold {{ $activa ? 'bg-white/20 text-white' : $estilo }}">{{ $contador }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </nav>
            </div>

            <div class="flex flex-col gap-1 bg-white/5 p-4">
                <a href="{{ route('inicio') }}" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm hover:bg-white/10 hover:text-white"><x-icono nombre="tienda" class="size-4.5" /> Ver la tienda</a>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg px-2.5 py-2 text-sm hover:bg-white/10 hover:text-white"><x-icono nombre="usuario" class="size-4.5" /> Mi perfil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-2.5 py-2 text-sm text-red-300 hover:bg-red-500/10 hover:text-red-200"><x-icono nombre="salir" class="size-4.5" /> Cerrar sesión</button>
                </form>
            </div>
        </aside>

        <div class="flex min-h-screen flex-col lg:pl-72">
            <header class="sticky top-0 z-20 flex items-center gap-3 bg-white/90 px-4 py-3 shadow-[0_1px_8px_rgba(0,0,0,0.06)] backdrop-blur sm:px-6">
                <button type="button" class="rounded-md p-1 text-slate-600 hover:bg-slate-100 lg:hidden" @click="menu = true" aria-label="Abrir menú">
                    <x-icono nombre="menu" class="size-6" />
                </button>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold tracking-wider text-slate-500 uppercase">Panel ForjaPC · {{ $rolVisto->etiqueta() }}</p>
                    <h1 class="truncate font-display text-lg font-bold text-slate-900">{{ $title ?? 'Panel' }}</h1>
                </div>
                <span class="ml-auto hidden text-xs text-slate-500 sm:block">{{ now()->translatedFormat('l j \d\e F') }}</span>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @if ($usuario->estaEnVistaPrevia())
                    <div class="mb-4 flex flex-col gap-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-300 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-2">
                            <x-icono nombre="usuario" class="size-5 shrink-0" />
                            <span>Estás viendo el panel como <strong>{{ $rolVisto->etiqueta() }}</strong>. Es una vista previa de solo lectura.</span>
                        </p>
                        <form method="POST" action="{{ route('admin.vista-previa.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg bg-amber-600 px-3 py-1.5 font-semibold text-white hover:bg-amber-700">Salir de la vista previa</button>
                        </form>
                    </div>
                @endif

                @if (session('estado'))
                    <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-emerald-200">{{ session('estado') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
