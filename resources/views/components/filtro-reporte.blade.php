@props(['desde' => null, 'hasta' => null, 'soloHasta' => false])

<form method="GET" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
    {{ $extra ?? '' }}
    @unless ($soloHasta)
        <div>
            <label for="desde" class="text-xs font-medium text-slate-600">Desde</label>
            <x-text-input id="desde" name="desde" type="date" :value="$desde?->toDateString()" class="mt-1 block" />
        </div>
    @endunless
    <div>
        <label for="hasta" class="text-xs font-medium text-slate-600">{{ $soloHasta ? 'Al' : 'Hasta' }}</label>
        <x-text-input id="hasta" name="hasta" type="date" :value="$hasta?->toDateString()" class="mt-1 block" />
    </div>
    <x-secondary-button type="submit">Consultar</x-secondary-button>
    <a href="{{ request()->fullUrlWithQuery(['formato' => 'csv']) }}" class="ml-auto inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
        <x-icono nombre="lista" class="size-4" /> Exportar CSV
    </a>
</form>
