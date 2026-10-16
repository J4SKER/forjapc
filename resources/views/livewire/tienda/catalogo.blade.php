<div x-data="{ filtrosMovil: false }" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    {{-- Encabezado --}}
    <nav class="text-xs text-slate-500" aria-label="Ruta">
        <a href="{{ route('inicio') }}" class="hover:text-marca-600">Inicio</a>
        <span class="mx-1">/</span>
        <span class="text-slate-700">{{ $categoria?->nombre ?? 'Búsqueda' }}</span>
    </nav>

    <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">
                @if ($categoria)
                    {{ $categoria->nombre }}
                @elseif (trim($q) !== '')
                    Resultados para «{{ $q }}»
                @else
                    Todos los productos
                @endif
            </h1>
            <p class="mt-1 text-sm text-slate-500">{{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="filtrosMovil = true" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium lg:hidden">
                <x-icono nombre="ajustes" class="size-4" /> Filtros
            </button>
            <label for="orden" class="sr-only">Ordenar</label>
            <select id="orden" wire:model.live="orden" class="rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-marca-500 focus:ring-marca-500">
                <option value="relevancia">{{ trim($q) !== '' ? 'Más relevantes' : 'Destacados' }}</option>
                <option value="precio_asc">Precio: menor a mayor</option>
                <option value="precio_desc">Precio: mayor a menor</option>
                <option value="nuevos">Más recientes</option>
            </select>
        </div>
    </div>

    @if ($sugerencia)
        <p class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
            No encontramos resultados. ¿Quisiste decir
            <button type="button" wire:click="$set('q', @js($sugerencia))" class="font-semibold underline">{{ $sugerencia }}</button>?
        </p>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[16rem_1fr]">
        {{-- Filtros --}}
        <div x-show="filtrosMovil" x-cloak class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="filtrosMovil = false"></div>
        <aside class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85%] -translate-x-full overflow-y-auto bg-white p-5 shadow-xl transition-transform lg:static lg:z-auto lg:w-auto lg:max-w-none lg:translate-x-0 lg:self-start lg:rounded-xl lg:shadow-sm lg:ring-1 lg:ring-slate-200"
               :class="filtrosMovil && 'translate-x-0'" aria-label="Filtros">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Filtrar</h2>
                <div class="flex items-center gap-3">
                    @if ($hayFiltros)
                        <button type="button" wire:click="limpiarFiltros" class="text-xs font-medium text-acento-600 hover:underline">Limpiar</button>
                    @endif
                    <button type="button" @click="filtrosMovil = false" class="lg:hidden" aria-label="Cerrar filtros"><x-icono nombre="cerrar" /></button>
                </div>
            </div>

            <label class="mt-4 flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="soloDisponibles" class="rounded border-slate-300 text-marca-600 focus:ring-marca-500">
                Solo con existencia
            </label>

            <fieldset class="mt-5">
                <legend class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Precio (Q)</legend>
                <div class="mt-2 flex items-center gap-2">
                    <input type="number" min="0" wire:model.live.debounce.600ms="min" placeholder="Mín." aria-label="Precio mínimo" class="w-full rounded-lg border-slate-300 text-sm">
                    <span class="text-slate-400">–</span>
                    <input type="number" min="0" wire:model.live.debounce.600ms="max" placeholder="Máx." aria-label="Precio máximo" class="w-full rounded-lg border-slate-300 text-sm">
                </div>
            </fieldset>

            @if ($marcasDisponibles->count() > 1)
                <fieldset class="mt-5">
                    <legend class="text-xs font-semibold tracking-wide text-slate-500 uppercase">Marca</legend>
                    <div class="mt-2 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                        @foreach ($marcasDisponibles as $marca)
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" value="{{ $marca->id }}" wire:model.live="marcas" class="rounded border-slate-300 text-marca-600 focus:ring-marca-500">
                                {{ $marca->nombre }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            @foreach ($filtrosTecnicos as $campo => $filtro)
                <fieldset class="mt-5">
                    <legend class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $filtro['etiqueta'] }}</legend>
                    <select wire:model.live="tecnicos.{{ $campo }}" aria-label="{{ $filtro['etiqueta'] }}" class="mt-2 w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Todos</option>
                        @foreach ($filtro['opciones'] as $opcion)
                            <option value="{{ $opcion['valor'] }}">{{ $opcion['texto'] }} ({{ $opcion['total'] }})</option>
                        @endforeach
                    </select>
                </fieldset>
            @endforeach
        </aside>

        {{-- Resultados --}}
        <div wire:loading.class="opacity-60" class="transition-opacity">
            @if ($productos->isEmpty())
                <div class="rounded-xl bg-white p-12 text-center shadow-sm ring-1 ring-slate-200">
                    <x-icono nombre="buscar" class="mx-auto size-12 text-slate-300" />
                    <p class="mt-3 font-semibold text-slate-800">No hay productos que coincidan.</p>
                    <p class="mt-1 text-sm text-slate-500">Prueba con menos filtros u otra búsqueda.</p>
                    @if ($hayFiltros)
                        <button type="button" wire:click="limpiarFiltros" class="mt-4 text-sm font-semibold text-acento-600 hover:underline">Limpiar filtros</button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($productos as $producto)
                        <x-producto-card :producto="$producto" wire:key="producto-{{ $producto->id }}" />
                    @endforeach
                </div>

                <div class="mt-6">{{ $productos->links() }}</div>
            @endif
        </div>
    </div>
</div>
