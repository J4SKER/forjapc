<x-admin-layout title="Períodos contables">
    <p class="mb-4 text-sm text-slate-600">Los períodos se abren solos con la primera partida del mes. Al cerrar uno ya no se pueden registrar partidas con fecha de ese mes; ciérralos en orden.</p>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="px-4 py-3">Período</th><th class="px-4 py-3 text-right">Partidas</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($periodos as $periodo)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $periodo->nombre() }}</td>
                        <td class="px-4 py-3 text-right">{{ $periodo->partidas_count }}</td>
                        <td class="px-4 py-3">
                            @if ($periodo->estaCerrado())
                                <span class="text-xs font-semibold text-slate-500">Cerrado por {{ $periodo->cerradoPor?->nombre }} el {{ $periodo->cerrado_at?->format('d/m/Y') }}</span>
                            @else
                                <span class="text-xs font-semibold text-emerald-700">Abierto</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if (! $periodo->estaCerrado() && Auth::user()->tieneRolEfectivo(\App\Enums\Rol::Admin, \App\Enums\Rol::Contador))
                                <form method="POST" action="{{ route('admin.contabilidad.periodos.cerrar', $periodo) }}" data-confirmar="¿Cerrar {{ $periodo->nombre() }}? No se podrán registrar más partidas en ese mes.">
                                    @csrf
                                    <button type="submit" class="text-xs font-semibold text-acento-700 hover:underline">Cerrar período</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Todavía no hay períodos.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
