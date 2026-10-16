<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-acento-600">Configurador de PC</p>
            <h1 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">Arma tu PC a la medida</h1>
            <p class="mt-1 text-sm text-slate-500">Solo te mostramos piezas compatibles con lo que ya elegiste y con existencia.</p>
        </div>
        @unless ($seleccionado->vacia())
            <button type="button" wire:click="vaciar" wire:confirm="¿Empezar de nuevo? Se quitarán todas las piezas." class="text-sm font-medium text-slate-500 hover:text-acento-600">Empezar de nuevo</button>
        @endunless
    </div>

    {{-- Pasos --}}
    <nav class="mt-5 flex gap-2 overflow-x-auto pb-2" aria-label="Pasos del configurador">
        @foreach ($pasos as $indice => $paso)
            @php($elegido = $seleccionado->tiene($paso))
            <button type="button" wire:click="irA('{{ $paso->value }}')" @class([
                'flex shrink-0 items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition',
                'border-marca-600 bg-marca-600 text-white' => $pasoActual === $paso,
                'border-emerald-300 bg-emerald-50 text-emerald-800' => $pasoActual !== $paso && $elegido,
                'border-slate-200 bg-white text-slate-600 hover:border-marca-400' => $pasoActual !== $paso && ! $elegido,
            ])>
                <span class="flex size-5 items-center justify-center rounded-full bg-black/10 text-xs">{{ $elegido ? '✓' : $indice + 1 }}</span>
                {{ $paso->etiqueta() }}
            </button>
        @endforeach
    </nav>

    @if ($mensaje)
        <div class="mt-3 rounded-lg border border-marca-100 bg-marca-50 px-4 py-2 text-sm text-marca-800">{{ $mensaje }}</div>
    @endif

    <div class="mt-4 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        {{-- Candidatos del paso actual --}}
        <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-bold text-slate-900">
                    Elige {{ mb_strtolower($pasoActual->etiqueta()) }}
                    @if ($pasoActual === \App\Enums\TipoComponente::Almacenamiento)
                        <span class="text-sm font-normal text-slate-500">(puedes elegir varias unidades)</span>
                    @elseif (! in_array($pasoActual, \App\Support\Configurador\Configurador::OBLIGATORIOS, true))
                        <span class="text-sm font-normal text-slate-500">(opcional)</span>
                    @endif
                </h2>
                <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar en este paso…" aria-label="Buscar en este paso"
                       class="w-full rounded-lg border-slate-300 text-sm sm:w-56">
            </div>

            @if ($pasoActual === \App\Enums\TipoComponente::Disipador && $seleccionado->producto(\App\Enums\TipoComponente::Cpu)?->especificacionCpu?->incluye_disipador)
                <p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">Tu procesador ya incluye disipador de fábrica. Este paso es opcional.</p>
            @endif

            <ul class="mt-4 divide-y divide-slate-100" wire:loading.class="opacity-60">
                @forelse ($candidatos as $producto)
                    @php($yaElegido = isset($seleccion[$pasoActual->value][$producto->id]))
                    <li wire:key="candidato-{{ $producto->id }}" class="flex items-center gap-4 py-3">
                        <div class="flex size-16 shrink-0 items-center justify-center rounded-lg bg-slate-50">
                            @if ($url = $producto->imagenPrincipalUrl())
                                <img src="{{ $url }}" alt="" class="h-full w-full object-contain p-1">
                            @else
                                <x-icono :nombre="$producto->categoria->icono" class="size-8 text-slate-300" />
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('tienda.producto', $producto->slug) }}" target="_blank" class="line-clamp-1 font-semibold text-slate-900 hover:text-marca-700">{{ $producto->nombre }}</a>
                            <p class="text-xs text-slate-500">{{ $producto->especificacion()?->resumen() }}</p>
                            <p class="text-xs {{ $producto->stockBajo() ? 'text-amber-700' : 'text-emerald-700' }}">{{ $producto->disponible() }} disponible(s) · {{ $producto->consumo_watts }} W</p>
                        </div>
                        <div class="text-right">
                            <p class="font-bold text-slate-900">Q{{ number_format($producto->precio, 2) }}</p>
                            @if ($yaElegido)
                                <span class="mt-1 inline-block rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">Elegido</span>
                            @else
                                <button type="button" wire:click="elegir({{ $producto->id }})" class="mt-1 rounded-lg bg-marca-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-marca-700">Elegir</button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="py-10 text-center text-sm text-slate-500">No hay productos compatibles y disponibles para este paso.</li>
                @endforelse
            </ul>

            @if ($ocultos > 0)
                <p class="mt-3 text-xs text-slate-500">Ocultamos {{ $ocultos }} producto(s) que no son compatibles con lo que ya elegiste.</p>
            @endif
        </section>

        {{-- Resumen --}}
        <aside class="space-y-4 lg:sticky lg:top-24">
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <h2 class="font-bold text-slate-900">Tu configuración</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($pasos as $paso)
                        @php($lineas = $seleccionado->de($paso))
                        <li class="rounded-lg bg-slate-50 px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <button type="button" wire:click="irA('{{ $paso->value }}')" class="text-xs font-semibold tracking-wide text-slate-500 uppercase hover:text-marca-600">{{ $paso->etiqueta() }}</button>
                                @if ($lineas === [] && in_array($paso, $faltantes, true))
                                    <span class="text-xs font-medium text-acento-600">Obligatorio</span>
                                @endif
                            </div>
                            @foreach ($lineas as $linea)
                                <div class="mt-1 flex items-start gap-2">
                                    <span class="flex-1 text-slate-800">{{ $linea['producto']->nombre }}</span>
                                    @if (\App\Support\Configurador\Configurador::maximo($paso) > 1)
                                        <select wire:change="cambiarCantidad('{{ $paso->value }}', {{ $linea['producto']->id }}, $event.target.value)" aria-label="Cantidad" class="rounded border-slate-300 py-0 pr-6 pl-1.5 text-xs">
                                            @for ($i = 1; $i <= \App\Support\Configurador\Configurador::maximo($paso); $i++)
                                                <option value="{{ $i }}" @selected($linea['cantidad'] === $i)>{{ $i }}×</option>
                                            @endfor
                                        </select>
                                    @endif
                                    <span class="font-medium whitespace-nowrap">Q{{ number_format($linea['producto']->precio * $linea['cantidad'], 2) }}</span>
                                    <button type="button" wire:click="quitar('{{ $paso->value }}', {{ $linea['producto']->id }})" class="text-slate-400 hover:text-acento-600" aria-label="Quitar"><x-icono nombre="cerrar" class="size-4" /></button>
                                </div>
                            @endforeach
                        </li>
                    @endforeach
                </ul>

                {{-- Compatibilidad --}}
                <div class="mt-4 space-y-2">
                    @foreach ($hallazgos as $hallazgo)
                        <div @class([
                            'rounded-lg px-3 py-2 text-sm',
                            'bg-red-50 text-red-800' => $hallazgo->esError(),
                            'bg-amber-50 text-amber-900' => ! $hallazgo->esError(),
                        ])>
                            <p class="font-semibold">{{ $hallazgo->esError() ? '✕' : '!' }} {{ $hallazgo->titulo }} <span class="text-xs font-normal opacity-70">({{ $hallazgo->codigo }})</span></p>
                            <p class="text-xs">{{ $hallazgo->mensaje }}</p>
                        </div>
                    @endforeach
                    @foreach ($sinExistencia as $producto)
                        <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">✕ {{ $producto->nombre }} ya no tiene existencia suficiente.</p>
                    @endforeach
                    @if ($hallazgos === [] && ! $seleccionado->vacia())
                        <p class="rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">✓ Todas las piezas son compatibles.</p>
                    @endif
                </div>

                <dl class="mt-4 space-y-1 border-t border-slate-100 pt-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Consumo estimado</dt><dd class="font-medium">{{ $consumo }} W</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Fuente recomendada</dt><dd class="font-medium">{{ $fuenteRecomendada ? $fuenteRecomendada.' W o más' : '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Componentes</dt><dd>Q{{ number_format($totalComponentes, 2) }}</dd></div>
                    <label class="flex items-center justify-between gap-2">
                        <span class="flex items-center gap-2 text-slate-500"><input type="checkbox" wire:model.live="incluyeArmado" class="rounded border-slate-300 text-marca-600"> Armado y pruebas</span>
                        <span>Q{{ number_format($costoArmado, 2) }}</span>
                    </label>
                    <div class="flex justify-between border-t border-slate-100 pt-2 text-base"><dt class="font-bold">Total</dt><dd class="font-extrabold">Q{{ number_format($totalComponentes + $costoArmado, 2) }}</dd></div>
                </dl>

                <div class="mt-4 space-y-2">
                    @include('tienda.partials.configuracion-al-carrito', ['puedeComprarse' => $puedeComprarse])

                    <div class="flex gap-2">
                        <input type="text" wire:model="nombre" maxlength="80" aria-label="Nombre de la configuración" class="min-w-0 flex-1 rounded-lg border-slate-300 text-sm">
                        <button type="button" wire:click="guardar" class="rounded-lg border border-slate-300 px-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Guardar</button>
                    </div>
                    @error('nombre') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @guest <p class="text-xs text-slate-500">Inicia sesión para guardar y compartir tu configuración.</p> @endguest

                    @if ($codigoGuardado)
                        <div x-data="{ copiado: false }" class="rounded-lg bg-slate-50 p-2 text-xs">
                            <p class="text-slate-500">Enlace para compartir:</p>
                            <div class="mt-1 flex gap-2">
                                <input type="text" readonly value="{{ route('tienda.configurador.ver', $codigoGuardado) }}" aria-label="Enlace para compartir" class="min-w-0 flex-1 rounded border-slate-200 bg-white text-xs" x-ref="enlace">
                                <button type="button" @click="navigator.clipboard.writeText($refs.enlace.value); copiado = true; setTimeout(() => copiado = false, 2000)" class="font-semibold text-marca-600" x-text="copiado ? 'Copiado' : 'Copiar'"></button>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <details class="rounded-2xl bg-white p-5 text-sm shadow-sm ring-1 ring-slate-200">
                <summary class="cursor-pointer font-semibold text-slate-900">¿Qué revisa el configurador? (18 reglas)</summary>
                <ol class="mt-3 space-y-1 text-xs text-slate-600">
                    @foreach ($reglas as $codigo => $texto)
                        <li><span class="font-mono text-slate-400">{{ $codigo }}</span> {{ $texto }}</li>
                    @endforeach
                </ol>
            </details>
        </aside>
    </div>
</div>
