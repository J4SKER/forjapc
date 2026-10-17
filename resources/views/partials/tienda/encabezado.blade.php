@php
    $categoriasMenu = \App\Models\Categoria::menu();
@endphp

<header x-data="{ menuMovil: false }" class="sticky top-0 z-40 bg-marca-800 text-white shadow-[0_4px_20px_rgba(0,0,0,0.25)]">
    {{-- Barra superior de contacto --}}
    <div class="hidden border-b border-white/5 text-xs text-marca-300 sm:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-1.5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-5">
                <span class="flex items-center gap-1.5"><x-icono nombre="telefono" class="size-3.5 text-cian-400" /> {{ \App\Models\ParametroSistema::valor('telefono_contacto') }}</span>
                <span class="flex items-center gap-1.5"><x-icono nombre="correo" class="size-3.5 text-cian-400" /> {{ \App\Models\ParametroSistema::valor('correo_contacto') }}</span>
            </div>
            <div class="flex items-center gap-5">
                <span class="flex items-center gap-1.5"><x-icono nombre="camion" class="size-3.5 text-cian-400" /> Envíos a los 22 departamentos</span>
                <a href="{{ route('pedidos.index') }}" class="hover:text-white">Rastrear mi pedido</a>
            </div>
        </div>
    </div>

    {{-- Encabezado principal --}}
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:gap-8 lg:px-8">
        <button type="button" class="-ml-1 rounded-lg p-1.5 hover:bg-white/10 lg:hidden" @click="menuMovil = true" aria-label="Abrir menú">
            <x-icono nombre="menu" class="size-6" />
        </button>

        <a href="{{ route('inicio') }}" class="flex min-w-0 shrink-0 items-center gap-2" aria-label="ForjaPC, ir al inicio">
            <x-application-logo class="size-9" />
            <span class="flex flex-col leading-tight">
                <span class="font-display text-lg font-bold tracking-tight sm:text-xl">ForjaPC</span>
                <span class="text-[10px] font-bold tracking-wider text-cian-400 uppercase">Hardware GT</span>
            </span>
        </a>

        <form action="{{ route('tienda.buscar') }}" method="GET" class="relative hidden flex-1 md:flex" role="search">
            <label for="buscar-escritorio" class="sr-only">Buscar productos</label>
            <x-icono nombre="buscar" class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-marca-300" />
            <input id="buscar-escritorio" type="search" name="q" value="{{ request('q') }}" placeholder="¿Qué estás buscando? Ej. RTX 4060, Ryzen 5, SSD 1TB…"
                   class="h-11 w-full rounded-lg border-0 bg-white/10 pr-24 pl-10 text-sm text-white placeholder:text-marca-300 focus:bg-white/15 focus:ring-2 focus:ring-cian-400">
            <button type="submit" class="absolute top-1.5 right-1.5 bottom-1.5 rounded-md bg-marca-600 px-4 text-sm font-semibold hover:bg-marca-500">Buscar</button>
        </form>

        <div class="ml-auto flex items-center gap-2 sm:gap-3 md:ml-0">
            @auth
                <div x-data="{ abierto: false }" class="relative" @click.outside="abierto = false" @keydown.escape="abierto = false">
                    <button type="button" @click="abierto = ! abierto" class="flex items-center gap-2 rounded-lg px-1.5 py-1.5 hover:bg-white/10" :aria-expanded="abierto">
                        <span class="flex size-8 items-center justify-center rounded-full bg-black"><x-icono nombre="usuario" class="size-4.5" /></span>
                        <span class="hidden text-left leading-tight lg:block">
                            <span class="block text-[11px] text-marca-300">Hola,</span>
                            <span class="block max-w-32 truncate text-sm font-semibold">{{ Str::before(Auth::user()->nombre, ' ') }}</span>
                        </span>
                        <x-icono nombre="chevron-abajo" class="hidden size-4 lg:block" />
                    </button>
                    <div x-show="abierto" x-transition x-cloak class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl bg-white py-1 text-sm text-slate-700 shadow-xl ring-1 ring-black/5">
                        @if (Auth::user()->esPersonal())
                            <a href="{{ route('admin.inicio') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="panel" class="size-4" /> Panel administrativo</a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="usuario" class="size-4" /> Mi cuenta</a>
                        <a href="{{ route('pedidos.index') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="paquete" class="size-4" /> Mis pedidos</a>
                        <a href="{{ route('direcciones.index') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="ubicacion" class="size-4" /> Mis direcciones</a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="ajustes" class="size-4" /> Perfil y seguridad</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-red-700 hover:bg-slate-50"><x-icono nombre="salir" class="size-4" /> Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="flex items-center gap-2 rounded-lg px-1.5 py-1.5 hover:bg-white/10">
                    <span class="flex size-8 items-center justify-center rounded-full bg-black"><x-icono nombre="usuario" class="size-4.5" /></span>
                    <span class="hidden text-left leading-tight lg:block">
                        <span class="block text-[11px] text-marca-300">Bienvenido</span>
                        <span class="block text-sm font-semibold">Ingresa o regístrate</span>
                    </span>
                </a>
            @endauth

            <livewire:tienda.contador-carrito />
        </div>
    </div>

    {{-- Buscador en teléfono --}}
    <form action="{{ route('tienda.buscar') }}" method="GET" class="relative px-4 pb-3 md:hidden" role="search">
        <label for="buscar-movil" class="sr-only">Buscar productos</label>
        <x-icono nombre="buscar" class="pointer-events-none absolute top-5 left-7 size-4.5 -translate-y-1/2 text-marca-300" />
        <input id="buscar-movil" type="search" name="q" value="{{ request('q') }}" placeholder="¿Qué estás buscando? Ej. RTX 4060, Ryzen 5"
               class="h-10 w-full rounded-lg border-0 bg-white/10 pl-9 text-sm text-white placeholder:text-marca-300 focus:bg-white/15 focus:ring-2 focus:ring-cian-400">
    </form>

    {{-- Categorías rápidas (desliza en teléfono) --}}
    <nav class="border-t border-white/5 bg-superficie text-slate-800" aria-label="Categorías">
        <div class="mx-auto flex max-w-7xl gap-2 overflow-x-auto px-4 py-2.5 scrollbar-none sm:px-6 lg:px-8 [&::-webkit-scrollbar]:hidden">
            <a href="{{ route('tienda.configurador') }}" @class([
                'flex shrink-0 items-center gap-1.5 rounded-xl bg-linear-to-r from-acento-500 via-orange-500 to-acento-600 px-3 py-2 text-xs font-bold text-white shadow-md shadow-orange-500/20 transition active:scale-95',
                'ring-2 ring-acento-600 ring-offset-2' => request()->routeIs('tienda.configurador'),
            ])>
                <x-icono nombre="herramienta" class="size-4" /> Arma tu PC
            </a>
            @foreach ($categoriasMenu as $categoria)
                @php($activa = request()->routeIs('tienda.categoria') && request()->route('slug') === $categoria['slug'])
                <a href="{{ route('tienda.categoria', $categoria['slug']) }}" @class([
                    'flex shrink-0 items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold transition',
                    'bg-marca-800 text-white' => $activa,
                    'bg-slate-200/70 text-slate-800 hover:bg-slate-300/70' => ! $activa,
                ])>
                    <x-icono :nombre="$categoria['icono']" @class(['size-4', 'text-cian-400' => $activa, 'text-marca-600' => ! $activa]) /> {{ $categoria['nombre'] }}
                </a>
            @endforeach
        </div>
    </nav>

    {{-- Menú lateral en teléfono --}}
    <div x-show="menuMovil" x-cloak class="fixed inset-0 z-50 text-slate-800 lg:hidden" role="dialog" aria-modal="true" aria-label="Menú">
        <div x-show="menuMovil" x-transition.opacity class="absolute inset-0 bg-black/50" @click="menuMovil = false"></div>
        <div x-show="menuMovil" x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full" x-transition:leave="transition duration-150" x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 left-0 flex w-80 max-w-[85%] flex-col overflow-y-auto bg-white shadow-xl">
            <div class="flex items-center justify-between bg-marca-800 px-4 py-3 text-white">
                <span class="font-display text-lg font-bold">ForjaPC</span>
                <button type="button" @click="menuMovil = false" class="rounded-lg p-1 hover:bg-white/10" aria-label="Cerrar menú"><x-icono nombre="cerrar" class="size-6" /></button>
            </div>
            <a href="{{ route('tienda.configurador') }}" class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 font-semibold text-acento-600">
                <x-icono nombre="herramienta" /> Arma tu PC
            </a>
            <p class="px-4 pt-4 pb-1 text-xs font-semibold tracking-wide text-slate-400 uppercase">Categorías</p>
            @foreach ($categoriasMenu as $categoria)
                <a href="{{ route('tienda.categoria', $categoria['slug']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                    <x-icono :nombre="$categoria['icono']" class="text-marca-600" /> {{ $categoria['nombre'] }}
                </a>
            @endforeach
            <p class="px-4 pt-4 pb-1 text-xs font-semibold tracking-wide text-slate-400 uppercase">Ayuda</p>
            <a href="{{ route('pedidos.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"><x-icono nombre="paquete" class="text-marca-600" /> Rastrear mi pedido</a>
            <a href="https://wa.me/{{ \App\Models\ParametroSistema::valor('whatsapp_contacto') }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50"><x-icono nombre="chat" class="text-emerald-600" /> Escríbenos por WhatsApp</a>
        </div>
    </div>
</header>
