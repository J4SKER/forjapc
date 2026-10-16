@use('App\Enums\EstadoOrdenArmado', 'E')
<x-admin-layout :title="'Armado · '.$orden->pedido->numero">
    @php
        $enProceso = in_array($orden->estado, [E::EnArmado, E::EnPruebas], true);
        $puedeTrabajar = Auth::user()->tieneRolEfectivo(\App\Enums\Rol::Admin, \App\Enums\Rol::Tecnico);
    @endphp

    <a href="{{ route('admin.taller.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver al taller</a>

    <div class="mt-4 grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-6">
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ $orden->nombre_configuracion }}</h2>
                        <p class="text-xs text-slate-500">Pedido {{ $orden->pedido->numero }} · {{ $orden->pedido->usuario->nombre }} · Técnico: {{ $orden->tecnico?->nombre ?? 'sin asignar' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $orden->estado->color() }}">{{ $orden->estado->etiqueta() }}</span>
                </div>
                <h3 class="mt-4 text-sm font-semibold text-slate-700">Piezas a ensamblar</h3>
                <ul class="mt-2 divide-y divide-slate-100 text-sm">
                    @foreach ($orden->piezas() as $pieza)
                        <li class="flex justify-between py-1.5"><span><span class="text-xs text-slate-400">{{ $pieza->producto->categoria->nombre }}:</span> {{ $pieza->descripcion }}</span><span>× {{ $pieza->cantidad }}</span></li>
                    @endforeach
                </ul>
            </section>

            <form method="POST" action="{{ route('admin.taller.checklist', $orden) }}" class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                @csrf
                <h2 class="font-semibold text-slate-900">Lista de verificación</h2>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach (\App\Models\OrdenArmado::CHECKLIST as $paso => $texto)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="checklist[{{ $paso }}]" value="0">
                            <input type="checkbox" name="checklist[{{ $paso }}]" value="1" @checked($orden->checklist[$paso] ?? false) @disabled(! $enProceso || ! $puedeTrabajar) class="rounded border-slate-300 text-marca-600">
                            {{ $texto }}
                        </label>
                    @endforeach
                </div>
                <div class="mt-3">
                    <x-input-label for="notas" value="Notas del técnico" />
                    <textarea id="notas" name="notas" rows="3" @disabled(! $enProceso || ! $puedeTrabajar) class="mt-1 block w-full rounded-lg border-slate-300 text-sm">{{ old('notas', $orden->notas) }}</textarea>
                </div>
                @if ($enProceso && $puedeTrabajar)
                    <x-secondary-button type="submit" class="mt-3">Guardar avance</x-secondary-button>
                @endif
            </form>

            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-semibold text-slate-900">Pruebas de estrés</h2>
                @if ($orden->pruebas)
                    <dl class="mt-3 grid grid-cols-2 gap-y-1 text-sm sm:grid-cols-4">
                        <dt class="text-slate-500">CPU máx.</dt><dd>{{ $orden->pruebas['temp_cpu_max'] }} °C</dd>
                        <dt class="text-slate-500">GPU máx.</dt><dd>{{ $orden->pruebas['temp_gpu_max'] ?? '—' }}{{ isset($orden->pruebas['temp_gpu_max']) ? ' °C' : '' }}</dd>
                        <dt class="text-slate-500">Duración</dt><dd>{{ $orden->pruebas['minutos_estres'] }} min</dd>
                        <dt class="text-slate-500">Resultado</dt><dd class="{{ $orden->pruebas['aprobada'] ? 'text-emerald-700' : 'text-red-700' }} font-semibold">{{ $orden->pruebas['aprobada'] ? 'Aprobada' : 'No aprobada' }}</dd>
                    </dl>
                    @if ($orden->pruebas['observaciones'] ?? null)<p class="mt-2 text-xs text-slate-500">{{ $orden->pruebas['observaciones'] }}</p>@endif
                @endif

                @if ($enProceso && $puedeTrabajar)
                    <form method="POST" action="{{ route('admin.taller.pruebas', $orden) }}" class="mt-4 grid gap-3 sm:grid-cols-3">
                        @csrf
                        <div><x-input-label for="temp_cpu_max" value="Temp. máx. CPU (°C)" /><x-text-input id="temp_cpu_max" name="temp_cpu_max" type="number" class="mt-1 block w-full" :value="old('temp_cpu_max')" required /></div>
                        <div><x-input-label for="temp_gpu_max" value="Temp. máx. GPU (°C)" /><x-text-input id="temp_gpu_max" name="temp_gpu_max" type="number" class="mt-1 block w-full" :value="old('temp_gpu_max')" /></div>
                        <div><x-input-label for="minutos_estres" value="Minutos de estrés" /><x-text-input id="minutos_estres" name="minutos_estres" type="number" class="mt-1 block w-full" :value="old('minutos_estres', 30)" required /></div>
                        <div class="sm:col-span-3"><x-input-label for="observaciones" value="Observaciones" /><x-text-input id="observaciones" name="observaciones" class="mt-1 block w-full" :value="old('observaciones')" /></div>
                        <label class="flex items-center gap-2 text-sm sm:col-span-3"><input type="checkbox" name="aprobada" value="1" class="rounded border-slate-300 text-marca-600"> El equipo pasó las pruebas sin errores ni temperaturas fuera de rango</label>
                        <x-input-error :messages="$errors->all()" class="sm:col-span-3" />
                        <div class="sm:col-span-3"><x-secondary-button type="submit">Registrar pruebas</x-secondary-button></div>
                    </form>
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            @if ($puedeTrabajar)
                <section class="space-y-2 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                    <h2 class="font-semibold text-slate-900">Acciones</h2>
                    @if (in_array($orden->estado, [E::Pendiente, E::Incidencia], true))
                        <form method="POST" action="{{ route('admin.taller.tomar', $orden) }}">@csrf<x-primary-button class="w-full">{{ $orden->estado === E::Incidencia ? 'Retomar orden' : 'Tomar orden' }}</x-primary-button></form>
                    @endif
                    @if ($orden->estado === E::EnPruebas && ($orden->pruebas['aprobada'] ?? false))
                        <form method="POST" action="{{ route('admin.taller.completar', $orden) }}" onsubmit="return confirm('¿Marcar el armado como completado?')">@csrf<x-primary-button class="w-full">Completar armado</x-primary-button></form>
                    @endif
                    @if ($enProceso)
                        <form method="POST" action="{{ route('admin.taller.incidencia', $orden) }}" class="space-y-2 border-t border-slate-100 pt-3">
                            @csrf
                            <x-text-input name="descripcion" placeholder="Describe la incidencia" class="block w-full" required />
                            <x-danger-button class="w-full">Reportar incidencia</x-danger-button>
                        </form>
                    @endif
                    @if ($orden->estado === E::Completada)
                        <p class="text-sm text-emerald-700">Completada el {{ $orden->completada_at->format('d/m/Y H:i') }}.</p>
                    @endif
                </section>
            @endif
            <a href="{{ route('admin.pedidos.show', $orden->pedido) }}" class="block rounded-xl bg-white p-4 text-center text-sm font-semibold text-marca-600 shadow-sm ring-1 ring-slate-200 hover:bg-slate-50">Ver pedido {{ $orden->pedido->numero }}</a>
        </aside>
    </div>
</x-admin-layout>
