@php
    // Etiquetas de los filtros aplicados, cada una con la acción que la quita.
    $activos = [];
    if ($soloDisponibles) {
        $activos[] = ['Solo con existencia', null, "\$set('soloDisponibles', false)"];
    }
    if ($min !== '' || $max !== '') {
        $rango = ($min !== '' ? 'Q'.number_format((float) $min) : 'Q0').' – '.($max !== '' ? 'Q'.number_format((float) $max) : 'sin límite');
        $activos[] = [$rango, 'Precio', "\$set('min', ''); \$set('max', '')"];
    }
    foreach ($marcasDisponibles->whereIn('id', $marcas) as $marca) {
        $restantes = array_values(array_diff(array_map('intval', $marcas), [$marca->id]));
        $activos[] = [$marca->nombre, 'Marca', '$set(\'marcas\', '.json_encode($restantes).')'];
    }
    foreach ($filtrosTecnicos as $campo => $filtro) {
        if (($tecnicos[$campo] ?? '') !== '') {
            $opcion = collect($filtro['opciones'])->firstWhere('valor', $tecnicos[$campo]);
            $activos[] = [$opcion['texto'] ?? $tecnicos[$campo], $filtro['etiqueta'], "\$set('tecnicos.{$campo}', '')"];
        }
    }
@endphp

<div x-data="{ filtrosMovil: false }" class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-6 sm:px-6 lg:px-8">
    {{-- Encabezado --}}
    <section class="flex flex-col gap-2">
        <nav class="flex items-center gap-1.5 text-xs text-slate-500" aria-label="Ruta">
            <a href="{{ route('inicio') }}" class="flex items-center gap-1 hover:text-marca-600"><x-icono nombre="tienda" class="size-4" /> Inicio</a>
            <x-icono nombre="chevron-derecha" class="size-3.5 text-slate-400" />
            <span class="font-medium text-slate-800">{{ $categoria?->nombre ?? 'Búsqueda' }}</span>
        </nav>

        <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-end">
            <div>
                @if ($categoria)
                    <span class="flex w-fit items-center gap-1.5 rounded bg-marca-100 px-2 py-0.5 text-[11px] font-bold tracking-wider text-marca-700 uppercase">
                        <x-icono :nombre="$categoria->icono" class="size-3.5" /> Categoría
                    </span>
                @endif
                <h1 class="mt-1 font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    @if ($categoria)
                        {{ $categoria->nombre }}
                    @elseif (trim($q) !== '')
                        Resultados para «{{ $q }}»
                    @else
                        Todos los productos
                    @endif
                </h1>
            </div>

            <div class="flex flex-wrap items-center gap-2 rounded-lg bg-white p-1.5 shadow-sm ring-1 ring-slate-200/70">
                <button type="button" @click="filtrosMovil = true" class="inline-flex items-center gap-1.5 rounded-md bg-marca-800 px-3 py-1.5 text-sm font-semibold text-white lg:hidden">
                    <x-icono nombre="ajustes" class="size-4" /> Filtrar{{ $activos !== [] ? ' ('.count($activos).')' : '' }}
                </button>
                <label for="orden" class="pl-2 text-sm whitespace-nowrap text-slate-500">Ordenar por:</label>
                <select id="orden" wire:model.live="orden" class="rounded-md border-slate-200 bg-slate-50 py-1.5 pr-8 pl-3 text-sm font-semibold focus:border-marca-600 focus:ring-marca-600">
                    <option value="relevancia">{{ trim($q) !== '' ? 'Más relevantes' : 'Destacados' }}</option>
                    <option value="precio_asc">Menor precio</option>
                    <option value="precio_desc">Mayor precio</option>
                    <option value="nuevos">Más recientes</option>
                </select>
            </div>
        </div>

        <div class="mt-1 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-100/80 p-2.5 ring-1 ring-slate-200/70">
            <div class="flex flex-wrap items-center gap-1.5">
                @if ($activos === [])
                    <span class="text-xs text-slate-500">Sin filtros: estás viendo todo{{ $categoria ? ' en '.mb_strtolower($categoria->nombre) : '' }}.</span>
                @else
                    <span class="mr-1 text-[11px] font-bold text-slate-500 uppercase">Filtros activos:</span>
                    @foreach ($activos as [$texto, $grupo, $quitar])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs text-slate-800 shadow-sm ring-1 ring-slate-200">
                            @if ($grupo)<span class="font-bold text-marca-600">{{ $grupo }}:</span>@else<span class="size-1.5 rounded-full bg-emerald-500"></span>@endif
                            {{ $texto }}
                            <button type="button" wire:click="{{ $quitar }}" class="text-slate-400 transition hover:text-red-600" aria-label="Quitar filtro {{ $texto }}"><x-icono nombre="cerrar" class="size-3.5" /></button>
                        </span>
                    @endforeach
                    <button type="button" wire:click="limpiarFiltros" class="ml-1 text-xs font-semibold text-marca-600 hover:underline">Limpiar filtros</button>
                @endif
            </div>
            <span class="text-xs text-slate-500">Mostrando <strong class="text-slate-900">{{ $productos->total() }}</strong> {{ $productos->total() === 1 ? 'producto' : 'productos' }}</span>
        </div>
    </section>

    @if ($sugerencia)
        <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">
            No encontramos resultados. ¿Quisiste decir
            <button type="button" wire:click="$set('q', @js($sugerencia))" class="font-semibold underline">{{ $sugerencia }}</button>?
        </p>
    @endif

    <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
        {{-- Filtros --}}
        <div x-show="filtrosMovil" x-cloak class="fixed inset-0 z-40 bg-black/40 lg:hidden" @click="filtrosMovil = false"></div>
        <aside class="fixed inset-y-0 left-0 z-50 flex w-80 max-w-[85%] -translate-x-full flex-col overflow-y-auto bg-white p-4 shadow-xl transition-transform lg:sticky lg:top-44 lg:z-auto lg:col-span-3 lg:w-auto lg:max-w-none lg:translate-x-0 lg:rounded-xl lg:shadow-sm lg:ring-1 lg:ring-slate-200/70"
               :class="filtrosMovil && 'translate-x-0'" aria-label="Filtros">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <h2 class="flex items-center gap-2 font-display text-lg font-bold text-slate-900"><x-icono nombre="ajustes" class="size-5 text-marca-600" /> Filtros</h2>
                <div class="flex items-center gap-3">
                    @if ($hayFiltros)
                        <button type="button" wire:click="limpiarFiltros" class="text-xs font-semibold text-marca-600 hover:underline">Limpiar todos</button>
                    @endif
                    <button type="button" @click="filtrosMovil = false" class="lg:hidden" aria-label="Cerrar filtros"><x-icono nombre="cerrar" /></button>
                </div>
            </div>

            <label class="group flex cursor-pointer items-center justify-between gap-3 border-b border-slate-200 py-4 select-none">
                <span class="flex flex-col">
                    <span class="text-sm font-semibold text-slate-900 group-hover:text-marca-600">Solo con existencia</span>
                    <span class="text-[11px] text-emerald-700">Listo para entrega inmediata</span>
                </span>
                <span class="relative">
                    <input type="checkbox" wire:model.live="soloDisponibles" class="peer sr-only">
                    <span class="block h-6 w-11 rounded-full bg-slate-300 transition peer-checked:bg-emerald-600 peer-focus-visible:ring-2 peer-focus-visible:ring-marca-600 peer-focus-visible:ring-offset-2"></span>
                    <span class="absolute top-0.5 left-0.5 size-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                </span>
            </label>

            <fieldset class="flex flex-col gap-2 border-b border-slate-200 py-4">
                <legend class="flex w-full items-center justify-between text-sm font-bold text-slate-900">Rango de precio <span class="text-[11px] font-normal text-slate-500">Quetzales</span></legend>
                <div class="mt-2 grid grid-cols-2 gap-2">
                    <label class="flex flex-col gap-0.5">
                        <span class="text-[10px] text-slate-500 uppercase">Mínimo</span>
                        <span class="relative flex items-center">
                            <span class="absolute left-2.5 text-xs text-slate-500">Q</span>
                            <input type="number" min="0" wire:model.live.debounce.600ms="min" placeholder="0" class="w-full rounded border-slate-200 bg-slate-50 py-1.5 pr-2 pl-6 text-sm focus:border-marca-600 focus:ring-marca-600">
                        </span>
                    </label>
                    <label class="flex flex-col gap-0.5">
                        <span class="text-[10px] text-slate-500 uppercase">Máximo</span>
                        <span class="relative flex items-center">
                            <span class="absolute left-2.5 text-xs text-slate-500">Q</span>
                            <input type="number" min="0" wire:model.live.debounce.600ms="max" placeholder="Sin límite" class="w-full rounded border-slate-200 bg-slate-50 py-1.5 pr-2 pl-6 text-sm focus:border-marca-600 focus:ring-marca-600">
                        </span>
                    </label>
                </div>
            </fieldset>

            @if ($marcasDisponibles->count() > 1)
                <fieldset class="flex flex-col gap-2 border-b border-slate-200 py-4">
                    <legend class="text-sm font-bold text-slate-900">Marca</legend>
                    <div class="mt-2 flex max-h-52 flex-col gap-1.5 overflow-y-auto pr-1">
                        @foreach ($marcasDisponibles as $marca)
                            <label class="flex cursor-pointer items-center gap-2 rounded px-1 py-0.5 text-sm text-slate-700 hover:bg-slate-50">
                                <input type="checkbox" value="{{ $marca->id }}" wire:model.live="marcas" class="rounded border-slate-300 text-marca-600 focus:ring-marca-600">
                                {{ $marca->nombre }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            @foreach ($filtrosTecnicos as $campo => $filtro)
                <fieldset class="flex flex-col gap-2 border-b border-slate-200 py-4 last:border-0">
                    <legend class="text-sm font-bold text-slate-900">{{ $filtro['etiqueta'] }}</legend>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <button type="button" wire:click="$set('tecnicos.{{ $campo }}', '')" @class([
                            'rounded-full px-2.5 py-1 text-xs font-semibold transition',
                            'bg-marca-800 text-white' => ($tecnicos[$campo] ?? '') === '',
                            'bg-slate-100 text-slate-700 hover:bg-slate-200' => ($tecnicos[$campo] ?? '') !== '',
                        ])>Todos</button>
                        @foreach ($filtro['opciones'] as $opcion)
                            @php
                                $elegida = (string) ($tecnicos[$campo] ?? '') === (string) $opcion['valor'];
                            @endphp
                            <button type="button" wire:click="$set('tecnicos.{{ $campo }}', @js((string) $opcion['valor']))" @class([
                                'rounded-full px-2.5 py-1 text-xs font-semibold transition',
                                'bg-marca-800 text-white' => $elegida,
                                'bg-slate-100 text-slate-700 hover:bg-slate-200' => ! $elegida,
                            ])>{{ $opcion['texto'] }} <span class="opacity-60">({{ $opcion['total'] }})</span></button>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            <button type="button" @click="filtrosMovil = false" class="mt-4 rounded-lg bg-marca-600 py-2.5 text-sm font-bold text-white lg:hidden">Ver {{ $productos->total() }} {{ $productos->total() === 1 ? 'producto' : 'productos' }}</button>
        </aside>

        {{-- Resultados --}}
        <div wire:loading.class="opacity-60" class="transition-opacity lg:col-span-9">
            @if ($productos->isEmpty())
                <div class="flex flex-col items-center gap-3 rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-slate-200/70">
                    <span class="flex size-16 items-center justify-center rounded-full bg-slate-100"><x-icono nombre="buscar" class="size-8 text-slate-400" /></span>
                    <p class="font-display text-lg font-bold text-slate-900">No hay productos que coincidan</p>
                    <p class="text-sm text-slate-500">Prueba con menos filtros u otra búsqueda.</p>
                    @if ($hayFiltros)
                        <button type="button" wire:click="limpiarFiltros" class="rounded-lg bg-marca-600 px-5 py-2 text-sm font-bold text-white hover:bg-marca-700">Limpiar filtros</button>
                    @endif
                </div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3">
                    @foreach ($productos as $producto)
                        <x-producto-card :producto="$producto" wire:key="producto-{{ $producto->id }}" />
                    @endforeach
                </div>

                <div class="mt-6">{{ $productos->links() }}</div>
            @endif
        </div>
    </div>
</div>
