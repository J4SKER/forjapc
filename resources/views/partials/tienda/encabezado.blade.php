@php
    $categoriasMenu = config('tienda.categorias');
@endphp

<header x-data="{ menuMovil: false, categorias: false }" class="sticky top-0 z-40 shadow-md">
    {{-- Barra superior de contacto --}}
    <div class="hidden bg-marca-900 text-xs text-marca-100 sm:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-1.5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-5">
                <span class="flex items-center gap-1.5"><x-icono nombre="telefono" class="size-3.5" /> {{ \App\Models\ParametroSistema::valor('telefono_contacto') }}</span>
                <span class="flex items-center gap-1.5"><x-icono nombre="correo" class="size-3.5" /> {{ \App\Models\ParametroSistema::valor('correo_contacto') }}</span>
            </div>
            <div class="flex items-center gap-5">
                <span class="flex items-center gap-1.5"><x-icono nombre="camion" class="size-3.5" /> Envíos a los 22 departamentos</span>
                <a href="{{ route('dashboard') }}" class="hover:text-white">Rastrear mi pedido</a>
            </div>
        </div>
    </div>

    {{-- Encabezado principal --}}
    <div class="bg-marca-800 text-white">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3 sm:px-6 lg:gap-8 lg:px-8">
            <button type="button" class="-ml-1 rounded-md p-1.5 hover:bg-marca-700 lg:hidden" @click="menuMovil = true" aria-label="Abrir menú">
                <x-icono nombre="menu" class="size-6" />
            </button>

            <a href="{{ route('inicio') }}" class="flex shrink-0 items-center gap-2" aria-label="ForjaPC, ir al inicio">
                <x-application-logo class="size-9" />
                <span class="text-xl font-extrabold tracking-tight sm:text-2xl">Forja<span class="text-acento-500">PC</span></span>
            </a>

            <form action="{{ route('tienda.buscar') }}" method="GET" class="hidden flex-1 md:flex" role="search">
                <label for="buscar-escritorio" class="sr-only">Buscar productos</label>
                <input id="buscar-escritorio" type="search" name="q" value="{{ request('q') }}" placeholder="¿Qué estás buscando? Ej. RTX 4060, Ryzen 5, SSD 1TB…"
                       class="w-full rounded-l-lg border-0 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-acento-500">
                <button type="submit" class="flex items-center rounded-r-lg bg-acento-600 px-5 hover:bg-acento-700" aria-label="Buscar">
                    <x-icono nombre="buscar" />
                </button>
            </form>

            <div class="ml-auto flex items-center gap-1 sm:gap-3 md:ml-0">
                @auth
                    <div x-data="{ abierto: false }" class="relative" @click.outside="abierto = false" @keydown.escape="abierto = false">
                        <button type="button" @click="abierto = ! abierto" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-marca-700" :aria-expanded="abierto">
                            <x-icono nombre="usuario" class="size-6" />
                            <span class="hidden text-left leading-tight lg:block">
                                <span class="block text-[11px] text-marca-100">Hola,</span>
                                <span class="block max-w-32 truncate text-sm font-semibold">{{ Str::before(Auth::user()->nombre, ' ') }}</span>
                            </span>
                            <x-icono nombre="chevron-abajo" class="hidden size-4 lg:block" />
                        </button>
                        <div x-show="abierto" x-transition x-cloak class="absolute right-0 mt-2 w-56 overflow-hidden rounded-lg bg-white py-1 text-sm text-slate-700 shadow-xl ring-1 ring-black/5">
                            @if (Auth::user()->esPersonal())
                                <a href="{{ route('admin.inicio') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="panel" class="size-4" /> Panel administrativo</a>
                            @endif
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="usuario" class="size-4" /> Mi cuenta</a>
                            <a href="{{ route('direcciones.index') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="ubicacion" class="size-4" /> Mis direcciones</a>
                            <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 hover:bg-slate-50"><x-icono nombre="ajustes" class="size-4" /> Perfil y seguridad</a>
                            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-acento-700 hover:bg-slate-50"><x-icono nombre="salir" class="size-4" /> Cerrar sesión</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-marca-700">
                        <x-icono nombre="usuario" class="size-6" />
                        <span class="hidden text-left leading-tight lg:block">
                            <span class="block text-[11px] text-marca-100">Bienvenido</span>
                            <span class="block text-sm font-semibold">Ingresa o regístrate</span>
                        </span>
                    </a>
                @endauth

                <a href="{{ route('carrito') }}" class="relative flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-marca-700" aria-label="Carrito de compras">
                    <span class="relative">
                        <x-icono nombre="carrito" class="size-6" />
                        <span class="absolute -top-2 -right-2 flex size-5 items-center justify-center rounded-full bg-acento-600 text-[11px] font-bold">0</span>
                    </span>
                    <span class="hidden text-left leading-tight lg:block">
                        <span class="block text-[11px] text-marca-100">Mi carrito</span>
                        <span class="block text-sm font-semibold">Q0.00</span>
                    </span>
                </a>
            </div>
        </div>

        {{-- Buscador en teléfono --}}
        <form action="{{ route('tienda.buscar') }}" method="GET" class="flex px-4 pb-3 md:hidden" role="search">
            <label for="buscar-movil" class="sr-only">Buscar productos</label>
            <input id="buscar-movil" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar productos…"
                   class="w-full rounded-l-lg border-0 bg-white px-3 py-2 text-sm text-slate-800 focus:ring-2 focus:ring-acento-500">
            <button type="submit" class="rounded-r-lg bg-acento-600 px-4" aria-label="Buscar"><x-icono nombre="buscar" /></button>
        </form>
    </div>

    {{-- Menú de categorías (escritorio) --}}
    <nav class="hidden border-b border-slate-200 bg-white lg:block" aria-label="Menú principal">
        <div class="mx-auto flex max-w-7xl items-center gap-1 px-4 sm:px-6 lg:px-8">
            <div class="relative" @mouseenter="categorias = true" @mouseleave="categorias = false">
                <button type="button" @click="categorias = ! categorias" class="flex items-center gap-2 bg-acento-600 px-5 py-3 text-sm font-semibold text-white hover:bg-acento-700" :aria-expanded="categorias">
                    <x-icono nombre="menu" class="size-4" /> Todas las categorías <x-icono nombre="chevron-abajo" class="size-4" />
                </button>
                <div x-show="categorias" x-transition.opacity x-cloak class="absolute left-0 z-50 grid w-[36rem] grid-cols-2 gap-1 rounded-b-lg bg-white p-3 shadow-xl ring-1 ring-black/5">
                    @foreach ($categoriasMenu as $categoria)
                        <a href="{{ route('tienda.categoria', $categoria['slug']) }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-slate-700 hover:bg-marca-50 hover:text-marca-700">
                            <x-icono :nombre="$categoria['icono']" class="size-5 text-marca-500" /> {{ $categoria['nombre'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <a href="{{ route('tienda.configurador') }}" class="flex items-center gap-1.5 px-4 py-3 text-sm font-semibold text-acento-600 hover:text-acento-700">
                <x-icono nombre="herramienta" class="size-4" /> Arma tu PC
            </a>
            @foreach (array_slice($categoriasMenu, 0, 4) as $categoria)
                <a href="{{ route('tienda.categoria', $categoria['slug']) }}" class="px-3 py-3 text-sm font-medium text-slate-700 hover:text-marca-600">{{ $categoria['nombre'] }}</a>
            @endforeach
            <a href="{{ route('tienda.categoria', 'monitores') }}" class="px-3 py-3 text-sm font-medium text-slate-700 hover:text-marca-600">Monitores</a>
            <a href="{{ route('tienda.categoria', 'teclados') }}" class="px-3 py-3 text-sm font-medium text-slate-700 hover:text-marca-600">Periféricos</a>
        </div>
    </nav>

    {{-- Menú lateral en teléfono --}}
    <div x-show="menuMovil" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menú">
        <div x-show="menuMovil" x-transition.opacity class="absolute inset-0 bg-black/50" @click="menuMovil = false"></div>
        <div x-show="menuMovil" x-transition:enter="transition duration-200" x-transition:enter-start="-translate-x-full" x-transition:leave="transition duration-150" x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 left-0 flex w-80 max-w-[85%] flex-col overflow-y-auto bg-white shadow-xl">
            <div class="flex items-center justify-between bg-marca-800 px-4 py-3 text-white">
                <span class="text-lg font-extrabold">Forja<span class="text-acento-500">PC</span></span>
                <button type="button" @click="menuMovil = false" class="rounded-md p-1 hover:bg-marca-700" aria-label="Cerrar menú"><x-icono nombre="cerrar" class="size-6" /></button>
            </div>
            <a href="{{ route('tienda.configurador') }}" class="flex items-center gap-3 border-b border-slate-100 px-4 py-3 font-semibold text-acento-600">
                <x-icono nombre="herramienta" /> Arma tu PC
            </a>
            <p class="px-4 pt-4 pb-1 text-xs font-semibold tracking-wide text-slate-400 uppercase">Categorías</p>
            @foreach ($categoriasMenu as $categoria)
                <a href="{{ route('tienda.categoria', $categoria['slug']) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                    <x-icono :nombre="$categoria['icono']" class="text-marca-500" /> {{ $categoria['nombre'] }}
                </a>
            @endforeach
        </div>
    </div>
</header>
