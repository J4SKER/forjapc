<x-admin-layout title="Libro mayor">
    <x-filtro-reporte :desde="$desde" :hasta="$hasta">
        <x-slot:extra>
            <div>
                <label for="cuenta" class="text-xs font-medium text-slate-600">Cuenta</label>
                <select id="cuenta" name="cuenta" class="mt-1 block rounded-lg border-slate-300 text-sm">
                    @foreach ($cuentas as $opcion)
                        <option value="{{ $opcion->id }}" @selected($mayor && $mayor['cuenta']->id === $opcion->id)>{{ $opcion->codigo }} · {{ $opcion->nombre }}</option>
                    @endforeach
                </select>
            </div>
        </x-slot:extra>
    </x-filtro-reporte>

    @if ($mayor)
        <section class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <div class="border-b border-slate-100 px-5 py-3">
                <h2 class="font-semibold text-slate-900">{{ $mayor['cuenta']->codigo }} · {{ $mayor['cuenta']->nombre }}</h2>
                <p class="text-xs text-slate-500">Naturaleza {{ mb_strtolower($mayor['cuenta']->naturaleza) }}</p>
            </div>
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr><th class="px-5 py-2">Fecha</th><th class="px-3 py-2">Descripción</th><th class="px-3 py-2 text-right">Debe</th><th class="px-3 py-2 text-right">Haber</th><th class="px-5 py-2 text-right">Saldo</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr class="bg-slate-50"><td class="px-5 py-2" colspan="4">Saldo inicial</td><td class="px-5 py-2 text-right font-semibold">Q{{ number_format($mayor['saldo_inicial'], 2) }}</td></tr>
                    @foreach ($mayor['movimientos'] as $movimiento)
                        <tr>
                            <td class="px-5 py-2 text-xs text-slate-500">{{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}</td>
                            <td class="px-3 py-2"><a href="{{ route('admin.contabilidad.partidas.show', $movimiento->partida_id) }}" class="hover:text-marca-700">{{ $movimiento->descripcion }}</a></td>
                            <td class="px-3 py-2 text-right">{{ $movimiento->debe > 0 ? 'Q'.number_format($movimiento->debe, 2) : '' }}</td>
                            <td class="px-3 py-2 text-right">{{ $movimiento->haber > 0 ? 'Q'.number_format($movimiento->haber, 2) : '' }}</td>
                            <td class="px-5 py-2 text-right">Q{{ number_format($movimiento->saldo, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-200 font-bold">
                    <tr><td colspan="4" class="px-5 py-2">Saldo final</td><td class="px-5 py-2 text-right">Q{{ number_format($mayor['saldo_final'], 2) }}</td></tr>
                </tfoot>
            </table>
        </section>
    @endif
</x-admin-layout>
