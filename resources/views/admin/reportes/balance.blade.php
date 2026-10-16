<x-admin-layout title="Balance de comprobación">
    <x-filtro-reporte :hasta="$hasta" :solo-hasta="true" />

    <section class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <h2 class="border-b border-slate-100 px-5 py-3 font-semibold text-slate-900">Sumas y saldos al {{ $hasta->format('d/m/Y') }}</h2>
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="px-5 py-2">Cuenta</th><th class="px-3 py-2 text-right">Debe</th><th class="px-3 py-2 text-right">Haber</th><th class="px-3 py-2 text-right">Saldo deudor</th><th class="px-5 py-2 text-right">Saldo acreedor</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($cuentas as $cuenta)
                    <tr>
                        <td class="px-5 py-2"><span class="font-mono text-xs text-slate-400">{{ $cuenta->codigo }}</span> {{ $cuenta->cuenta }}</td>
                        <td class="px-3 py-2 text-right">Q{{ number_format($cuenta->debe, 2) }}</td>
                        <td class="px-3 py-2 text-right">Q{{ number_format($cuenta->haber, 2) }}</td>
                        <td class="px-3 py-2 text-right">{{ $cuenta->saldo_deudor ? 'Q'.number_format($cuenta->saldo_deudor, 2) : '' }}</td>
                        <td class="px-5 py-2 text-right">{{ $cuenta->saldo_acreedor ? 'Q'.number_format($cuenta->saldo_acreedor, 2) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Sin movimientos.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="border-t-2 border-slate-200 font-bold">
                <tr>
                    <td class="px-5 py-2">Totales</td>
                    <td class="px-3 py-2 text-right">Q{{ number_format($totales['debe'], 2) }}</td>
                    <td class="px-3 py-2 text-right">Q{{ number_format($totales['haber'], 2) }}</td>
                    <td class="px-3 py-2 text-right">Q{{ number_format($totales['saldo_deudor'], 2) }}</td>
                    <td class="px-5 py-2 text-right">Q{{ number_format($totales['saldo_acreedor'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
        <p @class(['px-5 py-3 text-sm font-semibold', 'text-emerald-700' => abs($totales['debe'] - $totales['haber']) < 0.01, 'text-red-700' => abs($totales['debe'] - $totales['haber']) >= 0.01])>
            {{ abs($totales['debe'] - $totales['haber']) < 0.01 ? '✓ Las sumas del debe y el haber son iguales.' : '✕ Las sumas no cuadran.' }}
        </p>
    </section>
</x-admin-layout>
