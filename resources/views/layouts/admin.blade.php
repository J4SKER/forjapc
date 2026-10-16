@php
    use App\Enums\Rol;

    $usuario = Auth::user();

    // Secciones del panel. Las marcadas "pronto" se habilitan en los siguientes sprints.
    $secciones = [
        ['Inicio', 'panel', route('admin.inicio'), 'admin.inicio', [Rol::Admin, Rol::Vendedor, Rol::Tecnico, Rol::Contador], false],
        ['Pedidos', 'paquete', null, null, [Rol::Admin, Rol::Vendedor], true],
        ['Catálogo', 'tienda', null, null, [Rol::Admin, Rol::Vendedor], true],
        ['Inventario y compras', 'lista', null, null, [Rol::Admin, Rol::Vendedor, Rol::Contador], true],
        ['Taller de armado', 'herramienta', null, null, [Rol::Admin, Rol::Tecnico], true],
        ['Contabilidad', 'tarjeta', null, null, [Rol::Admin, Rol::Contador], true],
        ['Usuarios', 'usuarios', route('admin.usuarios.index'), 'admin.usuarios.*', [Rol::Admin], false],
        ['Parámetros', 'ajustes', route('admin.parametros.index'), 'admin.parametros.*', [Rol::Admin], false],
        ['Bitácora', 'candado', route('admin.bitacora.index'), 'admin.bitacora.*', [Rol::Admin], false],
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
                @foreach ($secciones as [$nombre, $icono, $url, $patron, $roles, $pronto])
                    @continue(! $usuario->tieneRol(...$roles))
                    @if ($pronto)
                        <span class="flex cursor-not-allowed items-center gap-3 rounded-lg px-3 py-2 text-sm text-marca-100/40" title="Disponible en un próximo sprint">
                            <x-icono :nombre="$icono" /> {{ $nombre }}
                            <span class="ml-auto text-[10px] uppercase">Pronto</span>
                        </span>
                    @else
                        <a href="{{ $url }}" @class([
                            'flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
                            'bg-marca-700 font-semibold text-white' => request()->routeIs($patron),
                            'hover:bg-marca-800 hover:text-white' => ! request()->routeIs($patron),
                        ])>
                            <x-icono :nombre="$icono" /> {{ $nombre }}
                        </a>
                    @endif
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

                <div x-data="{ abierto: false }" class="relative ml-auto" @click.outside="abierto = false">
                    <button type="button" @click="abierto = ! abierto" class="flex items-center gap-2 rounded-lg px-2 py-1 text-sm hover:bg-slate-100">
                        <span class="flex size-8 items-center justify-center rounded-full bg-marca-600 font-semibold text-white">{{ Str::upper(Str::substr($usuario->nombre, 0, 1)) }}</span>
                        <span class="hidden text-left leading-tight sm:block">
                            <span class="block font-semibold">{{ $usuario->nombre }}</span>
                            <span class="block text-xs text-slate-500">{{ $usuario->rol->etiqueta() }}</span>
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
                @if (session('estado'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('estado') }}</div>
                @endif

                {{ $slot }}
            </main>
        </div>

        @livewireScripts
    </body>
</html>
