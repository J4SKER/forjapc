<x-admin-layout title="Inicio">
    <p class="mb-6 text-slate-600">Bienvenido, <strong>{{ Auth::user()->nombre }}</strong>. Este es el resumen de hoy, {{ now()->translatedFormat('l j \d\e F') }}.</p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Clientes registrados', $totalClientes, "+{$clientesNuevosMes} este mes", 'usuarios', 'text-marca-600 bg-marca-50'],
            ['Personal activo', $totalPersonal, 'Ventas, taller, contabilidad y admin', 'usuario', 'text-emerald-600 bg-emerald-50'],
            ['Cuentas bloqueadas', $cuentasBloqueadas, 'Por intentos fallidos', 'candado', 'text-acento-600 bg-red-50'],
            ['Tipo de cambio', $tipoCambio ? 'Q'.number_format($tipoCambio->tasa, 5) : 'Sin registrar', $tipoCambio ? 'Del '.$tipoCambio->fecha->format('d/m/Y').' · '.$tipoCambio->fuente : 'Regístralo en Parámetros', 'cambio', 'text-amber-600 bg-amber-50'],
        ] as [$etiqueta, $valor, $detalle, $icono, $color])
            <div class="flex items-start gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <span class="rounded-lg p-2.5 {{ $color }}"><x-icono :nombre="$icono" class="size-6" /></span>
                <div>
                    <p class="text-sm text-slate-500">{{ $etiqueta }}</p>
                    <p class="text-2xl font-extrabold text-slate-900">{{ $valor }}</p>
                    <p class="text-xs text-slate-500">{{ $detalle }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 xl:col-span-2">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Actividad reciente</h2>
            <ul class="divide-y divide-slate-100">
                @forelse ($actividadReciente as $registro)
                    <li class="flex items-center justify-between gap-4 px-5 py-3 text-sm">
                        <span>
                            <span class="font-medium text-slate-800">{{ Str::of($registro->accion)->replace('_', ' ')->ucfirst() }}</span>
                            @if ($registro->entidad_tipo)
                                <span class="text-slate-500">· {{ $registro->entidad_tipo }} #{{ $registro->entidad_id }}</span>
                            @endif
                            <span class="block text-xs text-slate-500">{{ $registro->usuario?->nombre ?? 'Sistema' }}</span>
                        </span>
                        <time class="shrink-0 text-xs text-slate-400" datetime="{{ $registro->created_at->toIso8601String() }}">{{ $registro->created_at->diffForHumans() }}</time>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-500">Sin actividad registrada todavía.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold text-slate-900">Avance del proyecto</h2>
            <p class="mt-1 text-sm text-slate-500">Módulos según el cronograma.</p>
            <ol class="mt-4 space-y-2 text-sm">
                @foreach ([
                    ['Usuarios y seguridad', true], ['Catálogo e inventario', false], ['Configurador de PC', false],
                    ['Carrito y pedidos', false], ['Pagos y taller', false], ['Contabilidad y reportes', false],
                ] as [$modulo, $listo])
                    <li class="flex items-center gap-2">
                        <span @class(['size-2.5 rounded-full', 'bg-emerald-500' => $listo, 'bg-slate-300' => ! $listo])></span>
                        <span @class(['font-medium text-slate-800' => $listo, 'text-slate-500' => ! $listo])>{{ $modulo }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>
</x-admin-layout>
