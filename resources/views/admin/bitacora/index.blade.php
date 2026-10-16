<x-admin-layout title="Bitácora de auditoría">
    <form method="GET" class="mb-4 grid gap-3 sm:grid-cols-4">
        <div>
            <label for="accion" class="text-xs font-medium text-slate-600">Acción</label>
            <select id="accion" name="accion" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-marca-500 focus:ring-marca-500">
                <option value="">Todas</option>
                @foreach ($acciones as $accion)
                    <option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ \App\Models\BitacoraAuditoria::accionLegible($accion) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="desde" class="text-xs font-medium text-slate-600">Desde</label>
            <x-text-input id="desde" name="desde" type="date" :value="request('desde')" class="mt-1 block w-full" />
        </div>
        <div>
            <label for="hasta" class="text-xs font-medium text-slate-600">Hasta</label>
            <x-text-input id="hasta" name="hasta" type="date" :value="request('hasta')" class="mt-1 block w-full" />
        </div>
        <div class="flex items-end"><x-secondary-button type="submit">Filtrar</x-secondary-button></div>
    </form>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="px-4 py-3">Acción</th>
                    <th class="px-4 py-3">Registro</th>
                    <th class="px-4 py-3">Cambios</th>
                    <th class="px-4 py-3">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 align-top">
                @forelse ($registros as $registro)
                    <tr>
                        <td class="px-4 py-3 text-xs whitespace-nowrap text-slate-500">{{ $registro->created_at->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3">{{ $registro->usuario?->nombre ?? 'Sistema' }}</td>
                        <td class="px-4 py-3 font-medium">{{ \App\Models\BitacoraAuditoria::accionLegible($registro->accion) }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $registro->entidad_tipo ? "{$registro->entidadLegible()} #{$registro->entidad_id}" : '—' }}</td>
                        <td class="px-4 py-3 text-xs">
                            @foreach ($registro->datos_nuevos ?? [] as $campo => $valor)
                                <div>
                                    <span class="text-slate-500">{{ $campo }}:</span>
                                    @if (array_key_exists($campo, $registro->datos_anteriores ?? []))
                                        <span class="text-acento-700 line-through">{{ \App\Models\BitacoraAuditoria::valorLegible($registro->datos_anteriores[$campo]) }}</span> &rarr;
                                    @endif
                                    <span class="text-emerald-700">{{ \App\Models\BitacoraAuditoria::valorLegible($valor) }}</span>
                                </div>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $registro->ip }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No hay registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $registros->links() }}</div>
</x-admin-layout>
