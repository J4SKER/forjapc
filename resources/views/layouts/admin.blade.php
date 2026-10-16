@php
    use App\Enums\Rol;

    $usuario = Auth::user();

    $todos = [Rol::Admin, Rol::Vendedor, Rol::Tecnico, Rol::Contador];
    $ventas = [Rol::Admin, Rol::Vendedor];

    // Menú por grupos: [nombre, icono, ruta (null = próximamente), patrón activo, roles].
    $grupos = [
        '' => [
            ['Inicio', 'panel', 'admin.inicio', 'admin.inicio', $todos],
        ],
        'Ventas' => [
            ['Pedidos', 'paquete', 'admin.pedidos.index', 'admin.pedidos.*', [...$ventas, Rol::Contador]],
            ['Pagos por verificar', 'tarjeta', 'admin.pagos.index', 'admin.pagos.*', [...$ventas, Rol::Contador]],
        ],
        'Catálogo' => [
            ['Productos', 'tienda', 'admin.productos.index', 'admin.productos.*', $ventas],
            ['Categorías', 'lista', 'admin.categorias.index', 'admin.categorias.*', $ventas],
            ['Marcas', 'escudo', 'admin.marcas.index', 'admin.marcas.*', $ventas],
            ['Reseñas', 'chat', 'admin.resenas.index', 'admin.resenas.*', $ventas],
        ],
        'Inventario' => [
            ['Existencias y kardex', 'paquete', 'admin.inventario.index', 'admin.inventario.*', [...$ventas, Rol::Contador]],
            ['Compras', 'carrito', 'admin.compras.index', 'admin.compras.*', [...$ventas, Rol::Contador]],
            ['Proveedores', 'camion', 'admin.proveedores.index', 'admin.proveedores.*', $ventas],
        ],
        'Taller' => [
            ['Órdenes de armado', 'herramienta', 'admin.taller.index', 'admin.taller.*', [Rol::Admin, Rol::Tecnico, Rol::Vendedor]],
        ],
        'Finanzas' => [
            ['Contabilidad', 'tarjeta', null, null, [Rol::Admin, Rol::Contador]],
        ],
        'Sistema' => [
            ['Usuarios', 'usuarios', 'admin.usuarios.index', 'admin.usuarios.*', [Rol::Admin]],
            ['Parámetros', 'ajustes', 'admin.parametros.index', 'admin.parametros.*', [Rol::Admin]],
            ['Bitácora', 'candado', 'admin.bitacora.index', 'admin.bitacora.*', [Rol::Admin]],
        ],
    ];
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
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="bg-slate-100 font-sans text-slate-800 antialiased" x-data="{ menu: false }">
        {{-- Barra lateral --}}
        <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="menu = false"></div>
        <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-marca-900 text-marca-100 transition-transform lg:translate-x-0"
               :class="menu && 'translate-x-0'">
            <a href="{{ route('admin.inicio') }}" class="flex items-center gap-2 px-5 py-4 text-white">
                <x-application-logo class="size-8" />
                <span class="text-lg font-extrabold">Forja<span class="text-acento-500">PC</span></span>
                <span class="ml-auto rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase">Panel</span>
            </a>

            <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-2" aria-label="Panel administrativo">
                @foreach ($grupos as $grupo => $secciones)
                    @php($visibles = array_filter($secciones, fn ($s) => $usuario->tieneRolEfectivo(...$s[4])))
                    @continue($visibles === [])
                    @if ($grupo !== '')
                        <p class="px-3 pt-4 pb-1 text-[10px] font-semibold tracking-widest text-marca-100/50 uppercase">{{ $grupo }}</p>
                    @endif
                    @foreach ($visibles as [$nombre, $icono, $ruta, $patron])
                        @if ($ruta === null)
                            <span class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-sm text-marca-100/40" title="Disponible en un próximo sprint">
                                <x-icono :nombre="$icono" /> {{ $nombre }}
                                <span class="ml-auto text-[10px] uppercase">Pronto</span>
                            </span>
                        @else
                            <a href="{{ route($ruta) }}" @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
                                'bg-marca-700 font-semibold text-white' => request()->routeIs($patron),
                                'hover:bg-marca-800 hover:text-white' => ! request()->routeIs($patron),
                            ])>
                                <x-icono :nombre="$icono" /> {{ $nombre }}
                            </a>
                        @endif
                    @endforeach
                @endforeach
            </nav>

            <a href="{{ route('inicio') }}" class="m-3 flex items-center gap-2 rounded-lg border border-marca-700 px-3 py-2 text-sm hover:bg-marca-800 hover:text-white">
                <x-icono nombre="tienda" class="size-4" /> Ver la tienda
            </a>
        </aside>

        <div class="flex min-h-screen flex-col lg:pl-64">
            <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 sm:px-6">
                <button type="button" class="rounded-md p-1 text-slate-600 hover:bg-slate-100 lg:hidden" @click="menu = true" aria-label="Abrir menú">
                    <x-icono nombre="menu" class="size-6" />
                </button>
                <h1 class="truncate text-lg font-bold text-slate-900">{{ $title ?? 'Panel' }}</h1>

                @if ($usuario->rol === Rol::Admin)
                    <form method="POST" action="{{ route('admin.vista-previa.store') }}" class="ml-auto hidden items-center gap-2 sm:flex" x-data>
                        @csrf
                        <label for="ver-como" class="text-xs font-medium text-slate-500">Ver como</label>
                        <select id="ver-como" name="rol" @change="$el.form.submit()"
                                class="rounded-lg border-slate-300 py-1.5 pr-8 text-sm shadow-sm focus:border-marca-500 focus:ring-marca-500">
                            <option value="" @selected(! $usuario->estaEnVistaPrevia()) disabled>Administrador</option>
                            @foreach ([Rol::Vendedor, Rol::Tecnico, Rol::Contador] as $rolPrevia)
                                <option value="{{ $rolPrevia->value }}" @selected($usuario->rolEfectivo() === $rolPrevia)>{{ $rolPrevia->etiqueta() }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <div x-data="{ abierto: false }" @class(['relative', 'ml-auto' => $usuario->rol !== Rol::Admin]) @click.outside="abierto = false">
                    <button type="button" @click="abierto = ! abierto" class="flex items-center gap-2 rounded-lg px-2 py-1 text-sm hover:bg-slate-100">
                        <span class="flex size-8 items-center justify-center rounded-full bg-marca-600 font-semibold text-white">{{ Str::upper(Str::substr($usuario->nombre, 0, 1)) }}</span>
                        <span class="hidden text-left leading-tight sm:block">
                            <span class="block font-semibold">{{ $usuario->nombre }}</span>
                            <span class="block text-xs text-slate-500">{{ $usuario->rolEfectivo()->etiqueta() }}{{ $usuario->estaEnVistaPrevia() ? ' (vista previa)' : '' }}</span>
                        </span>
                        <x-icono nombre="chevron-abajo" class="size-4 text-slate-400" />
                    </button>
                    <div x-show="abierto" x-transition x-cloak class="absolute right-0 mt-2 w-48 rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-black/5">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-slate-50">Mi perfil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-acento-700 hover:bg-slate-50">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                @if ($usuario->estaEnVistaPrevia())
                    <div class="mb-4 flex flex-col gap-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                        <p class="flex items-center gap-2">
                            <x-icono nombre="usuario" class="size-5 shrink-0" />
                            <span>Estás viendo el panel como <strong>{{ $usuario->rolEfectivo()->etiqueta() }}</strong>. Es una vista previa de solo lectura.</span>
                        </p>
                        <form method="POST" action="{{ route('admin.vista-previa.destroy') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-lg bg-amber-600 px-3 py-1.5 font-semibold text-white hover:bg-amber-700">Salir de la vista previa</button>
                        </form>
                    </div>
                @endif

                @if (session('estado'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('estado') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
