<x-admin-layout title="Parámetros del sistema">
    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.parametros.update') }}" class="rounded-xl bg-white shadow-sm ring-1 ring-slate-200 xl:col-span-2">
            @csrf
            @method('PUT')

            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Parámetros del negocio</h2>
            <div class="divide-y divide-slate-100">
                @foreach ($parametros as $parametro)
                    @php($campo = "valores[{$parametro->clave}]")
                    <div class="grid gap-2 px-5 py-4 sm:grid-cols-2 sm:items-center">
                        <label for="p-{{ $parametro->clave }}">
                            <span class="block text-sm font-medium text-slate-800">{{ $parametro->descripcion }}</span>
                            <span class="block font-mono text-xs text-slate-400">{{ $parametro->clave }}</span>
                        </label>
                        <div>
                            @if ($parametro->tipo === 'booleano')
                                <input type="hidden" name="{{ $campo }}" value="0">
                                <input id="p-{{ $parametro->clave }}" type="checkbox" name="{{ $campo }}" value="1" class="rounded border-slate-300 text-marca-600 focus:ring-marca-500"
                                       @checked(old("valores.{$parametro->clave}", $parametro->valor) == '1')>
                            @else
                                <x-text-input id="p-{{ $parametro->clave }}" name="{{ $campo }}" class="block w-full"
                                              :type="in_array($parametro->tipo, ['entero', 'decimal']) ? 'number' : 'text'"
                                              :step="$parametro->tipo === 'decimal' ? '0.01' : '1'"
                                              :value="old('valores.'.$parametro->clave, $parametro->valor)" />
                            @endif
                            <x-input-error :messages="$errors->get('valores.'.$parametro->clave)" class="mt-1" />
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                <x-primary-button>Guardar parámetros</x-primary-button>
            </div>
        </form>

        <section class="h-fit rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
            <h2 class="border-b border-slate-100 px-5 py-4 font-semibold text-slate-900">Tipo de cambio (Q por US$)</h2>
            <form method="POST" action="{{ route('admin.parametros.tipo-cambio') }}" class="grid grid-cols-2 gap-3 px-5 py-4">
                @csrf
                <div>
                    <x-input-label for="fecha" value="Fecha" />
                    <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" :value="old('fecha', today()->toDateString())" :max="today()->toDateString()" required />
                    <x-input-error :messages="$errors->get('fecha')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="tasa" value="Tasa" />
                    <x-text-input id="tasa" name="tasa" type="number" step="0.00001" class="mt-1 block w-full" :value="old('tasa')" placeholder="7.75000" required />
                    <x-input-error :messages="$errors->get('tasa')" class="mt-1" />
                </div>
                <div class="col-span-2">
                    <x-secondary-button type="submit" class="w-full">Registrar tipo de cambio</x-secondary-button>
                    <p class="mt-2 text-xs text-slate-500">La sincronización automática con el Banco de Guatemala se agrega junto con los pagos.</p>
                </div>
            </form>
            <ul class="divide-y divide-slate-100 border-t border-slate-100 text-sm">
                @forelse ($tiposCambio as $tipo)
                    <li class="flex justify-between px-5 py-2">
                        <span class="text-slate-600">{{ $tipo->fecha->format('d/m/Y') }}</span>
                        <span class="font-medium">Q{{ number_format($tipo->tasa, 5) }} <span class="text-xs text-slate-400">{{ $tipo->fuente }}</span></span>
                    </li>
                @empty
                    <li class="px-5 py-4 text-center text-slate-500">Sin registros.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-admin-layout>
