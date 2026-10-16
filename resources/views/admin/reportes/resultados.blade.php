<x-admin-layout title="Estado de resultados">
    <x-filtro-reporte :desde="$desde" :hasta="$hasta" />

    <section class="max-w-3xl rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="text-center font-bold text-slate-900">ForjaPC</h2>
        <p class="text-center text-sm text-slate-500">Estado de resultados del {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} · cifras en quetzales</p>

        <dl class="mt-6 space-y-1 text-sm">
            <dt class="font-semibold text-slate-900">Ingresos</dt>
            @foreach ($ingresos as $cuenta)
                <div class="flex justify-between pl-4"><dd class="text-slate-600">{{ $cuenta->cuenta }}</dd><dd>{{ number_format($cuenta->monto, 2) }}</dd></div>
            @endforeach
            <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold"><dt>Total ingresos</dt><dd>{{ number_format($total_ingresos, 2) }}</dd></div>

            <dt class="pt-3 font-semibold text-slate-900">(−) Costo de ventas</dt>
            @foreach ($costos as $cuenta)
                <div class="flex justify-between pl-4"><dd class="text-slate-600">{{ $cuenta->cuenta }}</dd><dd>{{ number_format($cuenta->monto, 2) }}</dd></div>
            @endforeach
            <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold"><dt>Utilidad bruta</dt><dd>{{ number_format($utilidad_bruta, 2) }}</dd></div>

            <dt class="pt-3 font-semibold text-slate-900">(−) Gastos de operación</dt>
            @foreach ($gastos as $cuenta)
                <div class="flex justify-between pl-4"><dd class="text-slate-600">{{ $cuenta->cuenta }}</dd><dd>{{ number_format($cuenta->monto, 2) }}</dd></div>
            @endforeach
            <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold"><dt>Total gastos</dt><dd>{{ number_format($total_gastos, 2) }}</dd></div>

            <div @class(['mt-4 flex justify-between rounded-lg px-3 py-2 text-base font-extrabold', 'bg-emerald-50 text-emerald-800' => $utilidad_neta >= 0, 'bg-red-50 text-red-800' => $utilidad_neta < 0])>
                <dt>{{ $utilidad_neta >= 0 ? 'Utilidad' : 'Pérdida' }} del período</dt><dd>Q{{ number_format($utilidad_neta, 2) }}</dd>
            </div>
        </dl>
    </section>
</x-admin-layout>
