<x-admin-layout :title="'Partida '.$partida->referencia()">
    <a href="{{ route('admin.contabilidad.partidas.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a partidas</a>

    <div class="mt-4 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900">{{ $partida->descripcion }}</h2>
                <p class="text-xs text-slate-500">{{ $partida->fecha->format('d/m/Y') }} · {{ $partida->periodo->nombre() }} · {{ ['AUTOMATICA' => 'Automática', 'MANUAL' => 'Manual', 'REVERSION' => 'Reversión'][$partida->tipo] }} · {{ $partida->usuario?->nombre ?? 'Sistema' }}</p>
            </div>
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr><th class="px-5 py-2">Cuenta</th><th class="px-3 py-2 text-right">Debe</th><th class="px-5 py-2 text-right">Haber</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($partida->detalle as $linea)
                        <tr>
                            <td @class(['px-5 py-2', 'pl-12' => $linea->haber > 0])>
                                <span class="font-mono text-xs text-slate-400">{{ $linea->cuenta->codigo }}</span> {{ $linea->cuenta->nombre }}
                                @if ($linea->descripcion)<span class="block text-xs text-slate-500">{{ $linea->descripcion }}</span>@endif
                            </td>
                            <td class="px-3 py-2 text-right">{{ $linea->debe > 0 ? 'Q'.number_format($linea->debe, 2) : '' }}</td>
                            <td class="px-5 py-2 text-right">{{ $linea->haber > 0 ? 'Q'.number_format($linea->haber, 2) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-200 font-bold">
                    <tr><td class="px-5 py-2">Sumas iguales</td><td class="px-3 py-2 text-right">Q{{ number_format($partida->total_debe, 2) }}</td><td class="px-5 py-2 text-right">Q{{ number_format($partida->total_haber, 2) }}</td></tr>
                </tfoot>
            </table>
        </section>

        <aside class="space-y-4">
            @if ($partida->revierte)
                <a href="{{ route('admin.contabilidad.partidas.show', $partida->revierte) }}" class="block rounded-xl bg-white p-4 text-sm shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Revierte la partida <strong>{{ $partida->revierte->referencia() }}</strong></a>
            @endif
            @if ($partida->reversion)
                <a href="{{ route('admin.contabilidad.partidas.show', $partida->reversion) }}" class="block rounded-xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">Esta partida fue revertida por la <strong>{{ $partida->reversion->referencia() }}</strong></a>
            @elseif ($partida->tipo !== 'REVERSION' && Auth::user()->tieneRolEfectivo(\App\Enums\Rol::Admin, \App\Enums\Rol::Contador))
                <form method="POST" action="{{ route('admin.contabilidad.partidas.revertir', $partida) }}" class="space-y-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200" onsubmit="return confirm('¿Registrar una partida que revierte esta?')">
                    @csrf
                    <h2 class="font-semibold text-slate-900">Anular con reversión</h2>
                    <p class="text-xs text-slate-500">Las partidas no se borran: se registra otra con los montos invertidos.</p>
                    <x-text-input name="motivo" placeholder="Motivo" class="block w-full" required />
                    <x-danger-button class="w-full">Revertir partida</x-danger-button>
                </form>
            @endif
        </aside>
    </div>
</x-admin-layout>
