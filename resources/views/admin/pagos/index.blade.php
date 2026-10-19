<x-admin-layout title="Pagos">
    <div class="mb-4 flex gap-2 text-sm">
        @foreach (\App\Enums\EstadoPago::cases() as $opcion)
            <a href="{{ route('admin.pagos.index', ['estado' => $opcion->value]) }}" @class([
                'rounded-lg px-3 py-1.5 font-medium',
                'bg-marca-700 text-white' => $estado === $opcion,
                'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50' => $estado !== $opcion,
            ])>{{ $opcion->etiqueta() }}@if ($opcion === \App\Enums\EstadoPago::Pendiente) ({{ $porVerificar }})@endif</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($pagos as $pago)
            <article class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <a href="{{ route('admin.pedidos.show', $pago->pedido) }}" class="font-semibold text-marca-700 hover:underline">{{ $pago->pedido->numero }}</a>
                        <span class="text-sm text-slate-600">· {{ $pago->pedido->usuario->nombre }}</span>
                        <p class="text-xs text-slate-500">{{ $pago->metodo->etiqueta() }} · {{ $pago->created_at->format('d/m/Y H:i') }}
                            @if ($pago->banco) · {{ $pago->banco }}, boleta {{ $pago->numero_boleta }}@endif</p>
                        <p class="mt-1 text-sm">{{ $pago->metodo === \App\Enums\MetodoPago::Paypal ? 'Monto' : 'Declarado' }}: <strong>Q{{ number_format($pago->monto, 2) }}</strong> · Total del pedido: Q{{ number_format($pago->pedido->total, 2) }}
                            @if (abs($pago->monto - $pago->pedido->total) > 0.009)<span class="ml-1 rounded bg-red-50 px-1.5 text-xs font-semibold text-red-700">No coincide</span>@endif</p>
                        @if ($pago->metodo === \App\Enums\MetodoPago::Paypal && $pago->monto_usd)
                            <p class="text-xs text-slate-500">US${{ number_format($pago->monto_usd, 2) }} a Q{{ number_format($pago->tipo_cambio, 5) }} por dólar · Comisión de PayPal: Q{{ number_format($pago->comision, 2) }} · Orden {{ $pago->referencia_externa }}</p>
                        @endif
                        @if ($pago->comprobante_ruta)
                            <a href="{{ route('pagos.comprobante', $pago) }}" target="_blank" class="mt-1 inline-block text-xs font-semibold text-marca-600 hover:underline">Ver comprobante</a>
                        @endif
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $pago->estado->color() }}">{{ $pago->estado->etiqueta() }}</span>
                </div>

                @if ($pago->estado === \App\Enums\EstadoPago::Pendiente && $pago->comprobante_ruta)
                    <div class="mt-3 flex flex-wrap items-start gap-3 border-t border-slate-100 pt-3">
                        <form method="POST" action="{{ route('admin.pagos.aprobar', $pago) }}" data-confirmar="¿Confirmas que el dinero ya está en la cuenta?">
                            @csrf
                            <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Aprobar pago</button>
                        </form>
                        <form method="POST" action="{{ route('admin.pagos.rechazar', $pago) }}" class="flex flex-1 gap-2">
                            @csrf
                            <x-text-input name="motivo" placeholder="Motivo del rechazo" class="flex-1 text-sm" required />
                            <button type="submit" class="rounded-lg bg-slate-600 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Rechazar</button>
                        </form>
                    </div>
                @elseif ($pago->verificador)
                    <p class="mt-2 text-xs text-slate-500">Verificado por {{ $pago->verificador->nombre }} el {{ $pago->verificado_at?->format('d/m/Y H:i') }}. {{ $pago->motivo_rechazo }}</p>
                @elseif ($pago->estado === \App\Enums\EstadoPago::Aprobado && $pago->metodo === \App\Enums\MetodoPago::Paypal)
                    <p class="mt-2 text-xs text-slate-500">Confirmado automáticamente por PayPal el {{ $pago->verificado_at?->format('d/m/Y H:i') }}.</p>
                @elseif ($pago->motivo_rechazo)
                    <p class="mt-2 text-xs text-slate-500">{{ $pago->motivo_rechazo }}</p>
                @endif
            </article>
        @empty
            <p class="rounded-xl bg-white p-10 text-center text-slate-500 shadow-sm ring-1 ring-slate-200">No hay pagos en este estado.</p>
        @endforelse
    </div>
    <div class="mt-4">{{ $pagos->links() }}</div>
</x-admin-layout>
