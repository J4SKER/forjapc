<x-admin-layout title="Libro diario">
    <x-filtro-reporte :desde="$desde" :hasta="$hasta" />

    <div class="space-y-4">
        @forelse ($partidas as $partidaId => $lineas)
            @php($primera = $lineas->first())
            <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap justify-between gap-2 border-b border-slate-100 px-5 py-2.5 text-sm">
                    <a href="{{ route('admin.contabilidad.partidas.show', $partidaId) }}" class="font-semibold text-marca-700 hover:underline">
                        Partida {{ $primera->anio }}-{{ str_pad((string) $primera->mes, 2, '0', STR_PAD_LEFT) }}-{{ str_pad((string) $primera->numero, 4, '0', STR_PAD_LEFT) }}
                    </a>
                    <span class="text-slate-500">{{ \Carbon\Carbon::parse($primera->fecha)->format('d/m/Y') }} · {{ $primera->descripcion }}</span>
                </div>
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($lineas as $linea)
                            <tr>
                                <td @class(['px-5 py-1.5', 'pl-12' => $linea->haber > 0])><span class="font-mono text-xs text-slate-400">{{ $linea->codigo }}</span> {{ $linea->cuenta }}</td>
                                <td class="w-36 px-3 py-1.5 text-right">{{ $linea->debe > 0 ? 'Q'.number_format($linea->debe, 2) : '' }}</td>
                                <td class="w-36 px-5 py-1.5 text-right">{{ $linea->haber > 0 ? 'Q'.number_format($linea->haber, 2) : '' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @empty
            <p class="rounded-xl bg-white p-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">No hay partidas en el período.</p>
        @endforelse

        @if ($partidas->isNotEmpty())
            <div class="flex justify-end gap-8 rounded-xl bg-marca-900 px-5 py-3 text-sm font-bold text-white">
                <span>Total debe Q{{ number_format($totalDebe, 2) }}</span>
                <span>Total haber Q{{ number_format($totalHaber, 2) }}</span>
            </div>
        @endif
    </div>
</x-admin-layout>
