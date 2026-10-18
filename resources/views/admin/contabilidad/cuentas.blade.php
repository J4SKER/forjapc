<x-admin-layout title="Nomenclatura contable">
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_20rem]">
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                    <tr><th class="px-4 py-3">Código</th><th class="px-4 py-3">Cuenta</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Naturaleza</th><th class="px-4 py-3 text-right">Movimientos</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($cuentas as $cuenta)
                        <tr @class(['bg-slate-50 font-semibold' => ! $cuenta->acepta_movimientos])>
                            <td class="px-4 py-2 font-mono text-xs">{{ $cuenta->codigo }}</td>
                            <td class="px-4 py-2" style="padding-left: {{ 1 + substr_count($cuenta->codigo, '.') * 1.25 }}rem">
                                @if ($cuenta->acepta_movimientos)
                                    <a href="{{ route('admin.reportes.libro-mayor', ['cuenta' => $cuenta->id]) }}" class="hover:text-marca-700">{{ $cuenta->nombre }}</a>
                                @else
                                    {{ $cuenta->nombre }}
                                @endif
                            </td>
                            <td class="px-4 py-2 text-xs">{{ ucfirst(mb_strtolower($cuenta->tipo)) }}</td>
                            <td class="px-4 py-2 text-xs">{{ ucfirst(mb_strtolower($cuenta->naturaleza)) }}</td>
                            <td class="px-4 py-2 text-right text-xs">{{ $cuenta->acepta_movimientos ? $cuenta->movimientos_count : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (Auth::user()->tieneRolEfectivo(\App\Enums\Rol::Admin, \App\Enums\Rol::Contador))
            <form method="POST" action="{{ route('admin.contabilidad.cuentas.store') }}" class="space-y-3 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                @csrf
                <h2 class="font-semibold text-slate-900">Nueva cuenta de detalle</h2>
                <div>
                    <x-input-label for="padre_id" value="Cuenta de agrupación" />
                    <select id="padre_id" name="padre_id" class="mt-1 block w-full rounded-lg border-slate-300 text-sm" required>
                        @foreach ($agrupadoras->filter(fn ($c) => substr_count($c->codigo, '.') === 1) as $padre)
                            <option value="{{ $padre->id }}" @selected((int) old('padre_id') === $padre->id)>{{ $padre->codigo }} · {{ $padre->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="codigo" value="Código" />
                    <x-text-input id="codigo" name="codigo" class="mt-1 block w-full" :value="old('codigo')" placeholder="Ej. 6.1.04" required />
                </div>
                <div>
                    <x-input-label for="nombre" value="Nombre" />
                    <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" :value="old('nombre')" required />
                </div>
                <x-input-error :messages="$errors->all()" />
                <x-primary-button class="w-full">Crear cuenta</x-primary-button>
            </form>
        @endif
    </div>
</x-admin-layout>
