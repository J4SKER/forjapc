<x-admin-layout title="Partidas contables">
    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <form method="GET" class="grid flex-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <label for="q" class="text-xs font-medium text-slate-600">Descripción</label>
                <x-text-input id="q" name="q" :value="request('q')" placeholder="Ej. Venta del pedido FPC-000012" class="mt-1 block w-full" />
            </div>
            <div>
                <label for="periodo" class="text-xs font-medium text-slate-600">Período</label>
                <select id="periodo" name="periodo" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Todos</option>
                    @foreach ($periodos as $periodo)
                        <option value="{{ $periodo->id }}" @selected((int) request('periodo') === $periodo->id)>{{ $periodo->nombre() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <select name="tipo" aria-label="Tipo" class="block w-full rounded-lg border-slate-300 text-sm">
                    <option value="">Todos los tipos</option>
                    @foreach (['AUTOMATICA' => 'Automáticas', 'MANUAL' => 'Manuales', 'REVERSION' => 'Reversiones'] as $valor => $texto)
                        <option value="{{ $valor }}" @selected(request('tipo') === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
                <x-secondary-button type="submit">Filtrar</x-secondary-button>
            </div>
        </form>
        @if (Auth::user()->tieneRolEfectivo(\App\Enums\Rol::Admin, \App\Enums\Rol::Contador))
            <a href="{{ route('admin.contabilidad.partidas.create') }}" class="inline-flex items-center justify-center rounded-lg bg-acento-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-acento-700">Partida manual</a>
        @endif
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="px-4 py-3">Partida</th><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Descripción</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3 text-right">Monto</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($partidas as $partida)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs"><a href="{{ route('admin.contabilidad.partidas.show', $partida) }}" class="font-semibold text-marca-700 hover:underline">{{ $partida->referencia() }}</a></td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $partida->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">{{ $partida->descripcion }} @if ($partida->reversion)<span class="ml-1 rounded bg-slate-100 px-1.5 text-xs text-slate-500">revertida</span>@endif</td>
                        <td class="px-4 py-3 text-xs">{{ ['AUTOMATICA' => 'Automática', 'MANUAL' => 'Manual', 'REVERSION' => 'Reversión'][$partida->tipo] }}</td>
                        <td class="px-4 py-3 text-right font-medium">Q{{ number_format($partida->total_debe, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">No hay partidas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $partidas->links() }}</div>
</x-admin-layout>
