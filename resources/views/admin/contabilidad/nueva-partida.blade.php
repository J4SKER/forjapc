<x-admin-layout title="Partida manual">
    @php
        $lineas = old('lineas', [['codigo' => '', 'debe' => '', 'haber' => ''], ['codigo' => '', 'debe' => '', 'haber' => '']]);
    @endphp

    <a href="{{ route('admin.contabilidad.partidas.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a partidas</a>

    <form method="POST" action="{{ route('admin.contabilidad.partidas.store') }}" class="mt-4 max-w-4xl space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200"
          x-data="{
              lineas: @js(array_values($lineas)),
              get debe() { return this.lineas.reduce((s, l) => s + (Number(l.debe) || 0), 0) },
              get haber() { return this.lineas.reduce((s, l) => s + (Number(l.haber) || 0), 0) },
              get cuadra() { return this.debe > 0 && Math.abs(this.debe - this.haber) < 0.005 },
          }">
        @csrf
        <div class="grid gap-4 sm:grid-cols-4">
            <div>
                <x-input-label for="fecha" value="Fecha" />
                <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" :value="old('fecha', today()->toDateString())" :max="today()->toDateString()" required />
            </div>
            <div class="sm:col-span-3">
                <x-input-label for="descripcion" value="Descripción" />
                <x-text-input id="descripcion" name="descripcion" class="mt-1 block w-full" :value="old('descripcion')" placeholder="Ej. Pago a proveedor con transferencia" required />
            </div>
        </div>

        <table class="min-w-full text-sm">
            <thead class="text-left text-xs font-semibold tracking-wide text-slate-500 uppercase">
                <tr><th class="py-2">Cuenta</th><th class="w-36 px-2 py-2">Debe</th><th class="w-36 px-2 py-2">Haber</th><th class="w-8"></th></tr>
            </thead>
            <tbody>
                <template x-for="(linea, i) in lineas" :key="i">
                    <tr>
                        <td class="py-1.5">
                            <select :name="`lineas[${i}][codigo]`" x-model="linea.codigo" aria-label="Cuenta" class="w-full rounded-lg border-slate-300 text-sm">
                                <option value="">Selecciona una cuenta…</option>
                                @foreach ($cuentas as $cuenta)
                                    <option value="{{ $cuenta->codigo }}">{{ $cuenta->codigo }} · {{ $cuenta->nombre }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" :name="`lineas[${i}][debe]`" x-model="linea.debe" @input="if (linea.debe) linea.haber = ''" aria-label="Debe" class="w-full rounded-lg border-slate-300 text-sm"></td>
                        <td class="px-2 py-1.5"><input type="number" step="0.01" min="0" :name="`lineas[${i}][haber]`" x-model="linea.haber" @input="if (linea.haber) linea.debe = ''" aria-label="Haber" class="w-full rounded-lg border-slate-300 text-sm"></td>
                        <td><button type="button" @click="lineas.splice(i, 1)" x-show="lineas.length > 2" class="text-slate-400 hover:text-acento-600" aria-label="Quitar línea"><x-icono nombre="cerrar" class="size-4" /></button></td>
                    </tr>
                </template>
            </tbody>
            <tfoot class="border-t border-slate-200 font-semibold">
                <tr>
                    <td class="py-2"><button type="button" @click="lineas.push({ codigo: '', debe: '', haber: '' })" class="text-sm font-semibold text-marca-600 hover:underline">+ Agregar línea</button></td>
                    <td class="px-2 py-2" x-text="'Q' + debe.toFixed(2)"></td>
                    <td class="px-2 py-2" x-text="'Q' + haber.toFixed(2)"></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <p x-show="! cuadra" class="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">La partida debe cuadrar: el total del debe tiene que ser igual al del haber.</p>
        <x-input-error :messages="$errors->all()" />

        <x-primary-button x-bind:disabled="! cuadra">Registrar partida</x-primary-button>
    </form>
</x-admin-layout>
