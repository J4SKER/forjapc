@use('App\Enums\TipoComponente')
@use('App\Support\Configurador\Configurador')
@php
    $codigos = ['cpu' => 'CPU', 'placa' => 'PLACA', 'ram' => 'RAM', 'almacenamiento' => 'SSD', 'gpu' => 'GPU', 'gabinete' => 'GABINETE', 'disipador' => 'DISIPADOR', 'fuente' => 'FUENTE'];
    $elegidos = collect($pasos)->filter(fn ($paso) => $seleccionado->tiene($paso))->count();
    $piezas = collect($pasos)->sum(fn ($paso) => collect($seleccionado->de($paso))->sum('cantidad'));
    $total = $totalComponentes + $costoArmado;
    $carga = $fuenteRecomendada ? min(100, (int) round($consumo / $fuenteRecomendada * 100)) : 0;
@endphp

<div class="mx-auto flex max-w-7xl flex-col gap-5 px-4 py-6 pb-28 sm:px-6 lg:px-8 lg:pb-6">
    {{-- Encabezado --}}
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div class="flex flex-col gap-1">
            <span class="inline-flex w-fit items-center gap-1.5 rounded bg-marca-500/10 px-2 py-0.5 text-xs font-semibold tracking-wider text-marca-600 uppercase">
                <x-icono nombre="ajustes" class="size-4" /> Configurador de PC
            </span>
            <h1 class="font-display text-3xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Arma tu PC</h1>
            <p class="max-w-3xl text-slate-600 sm:text-lg">Solo te mostramos piezas compatibles con lo que ya elegiste y con existencia.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 rounded-lg bg-white px-4 py-2.5 text-xs shadow-sm ring-1 ring-slate-200/70">
            <span class="flex items-center gap-2 font-semibold text-slate-800"><span class="size-2.5 animate-pulse rounded-full bg-emerald-500 motion-reduce:animate-none"></span> Existencias en tiempo real</span>
            <span class="text-slate-300">·</span>
            <span class="flex items-center gap-1.5 font-semibold text-marca-600"><x-icono nombre="escudo" class="size-4" /> 18 reglas de compatibilidad</span>
            @unless ($seleccionado->vacia())
                <span class="text-slate-300">·</span>
                <button type="button" wire:click="vaciar" wire:confirm="¿Empezar de nuevo? Se quitarán todas las piezas." class="font-semibold text-slate-500 hover:text-red-600">Empezar de nuevo</button>
            @endunless
        </div>
    </div>

    {{-- Pasos --}}
    <nav class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-200/70" aria-label="Pasos del configurador">
        <ol class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-8">
            @foreach ($pasos as $indice => $paso)
                @php
                    $elegido = $seleccionado->tiene($paso);
                    $actual = $pasoActual === $paso;
                    $primera = $seleccionado->de($paso)[0]['producto'] ?? null;
                @endphp
                <li>
                    <button type="button" wire:click="irA('{{ $paso->value }}')" @if ($actual) aria-current="step" @endif @class([
                        'relative flex w-full flex-col overflow-hidden rounded p-2 text-left transition',
                        'bg-marca-800 text-white shadow-md' => $actual,
                        'bg-slate-100/80 hover:bg-slate-200/70' => ! $actual && $elegido,
                        'bg-white ring-1 ring-slate-200/70 hover:bg-slate-50' => ! $actual && ! $elegido,
                    ])>
                        @if ($actual)<span class="absolute inset-x-0 top-0 h-1 bg-marca-500"></span>@endif
                        <span class="mb-1 flex items-center justify-between">
                            <span @class(['text-[11px] font-bold', 'text-cian-400' => $actual, 'text-slate-500' => ! $actual])>{{ str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT) }}. {{ $codigos[$paso->value] ?? '' }}</span>
                            @if ($elegido && ! $actual)
                                <span class="flex size-5 items-center justify-center rounded-full bg-emerald-600 text-[11px] font-bold text-white">✓</span>
                            @else
                                <span @class(['flex size-5 items-center justify-center rounded-full text-[11px] font-bold', 'bg-marca-500 text-white' => $actual, 'bg-slate-200 text-slate-600' => ! $actual])>{{ $indice + 1 }}</span>
                            @endif
                        </span>
                        <span @class(['truncate text-xs font-bold', 'text-white' => $actual, 'text-slate-900' => ! $actual])>{{ $paso->etiqueta() }}</span>
                        @if ($actual)
                            <span class="text-[10px] font-bold tracking-wider text-cian-400 uppercase">Paso actual</span>
                        @elseif ($primera)
                            <span class="truncate text-[11px] font-medium text-emerald-700">{{ $primera->nombre }}</span>
                        @elseif (in_array($paso, Configurador::OBLIGATORIOS, true))
                            <span class="text-[10px] font-bold text-red-600 uppercase">Obligatorio</span>
                        @else
                            <span class="text-[10px] font-medium text-slate-500 uppercase">Opcional</span>
                        @endif
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>

    @if ($mensaje)
        <div class="rounded-lg bg-marca-50 px-4 py-2 text-sm text-marca-800 ring-1 ring-marca-100">{{ $mensaje }}</div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-12">
        {{-- Candidatos del paso actual --}}
        <section class="flex flex-col gap-4 lg:col-span-8">
            <div class="flex flex-col gap-3 rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200/70 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-display text-lg font-bold text-slate-900">
                    Elige {{ mb_strtolower($pasoActual->etiqueta()) }}
                    @if ($pasoActual === TipoComponente::Almacenamiento)
                        <span class="block text-xs font-normal text-slate-500">Puedes elegir varias unidades</span>
                    @elseif (! in_array($pasoActual, Configurador::OBLIGATORIOS, true))
                        <span class="block text-xs font-normal text-slate-500">Este paso es opcional</span>
                    @endif
                </h2>
                <div class="relative w-full sm:w-72">
                    <x-icono nombre="buscar" class="pointer-events-none absolute top-1/2 left-3 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="search" wire:model.live.debounce.400ms="buscar" placeholder="Buscar en este paso…" aria-label="Buscar en este paso"
                           class="w-full rounded-lg border-0 bg-slate-100 py-2.5 pl-10 text-sm focus:bg-white focus:ring-1 focus:ring-marca-600">
                </div>
            </div>

            @if ($pasoActual === TipoComponente::Disipador && $seleccionado->producto(TipoComponente::Cpu)?->especificacionCpu?->incluye_disipador)
                <p class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Tu procesador ya incluye disipador de fábrica. Este paso es opcional.</p>
            @endif

            @if ($ocultos > 0)
                <div class="flex items-center gap-3 rounded-lg bg-marca-600/5 p-4 shadow-sm">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded bg-marca-600/10 text-marca-600"><x-icono nombre="escudo" class="size-5" /></span>
                    <p class="text-sm text-slate-800"><span class="font-bold text-marca-600">Filtro de compatibilidad activo:</span> Ocultamos {{ $ocultos }} {{ Str::plural('producto', $ocultos) }} que no {{ $ocultos === 1 ? 'es compatible' : 'son compatibles' }} con lo que ya elegiste o no {{ $ocultos === 1 ? 'tiene' : 'tienen' }} existencia.</p>
                </div>
            @endif

            <ul class="flex flex-col gap-3" wire:loading.class="opacity-60">
                @forelse ($candidatos as $producto)
                    @php
                        $yaElegido = isset($seleccion[$pasoActual->value][$producto->id]);
                    @endphp
                    <li wire:key="candidato-{{ $producto->id }}" @class([
                        'group relative flex flex-col items-stretch justify-between gap-4 overflow-hidden rounded-lg bg-white p-4 transition md:flex-row md:items-center',
                        'shadow-md ring-2 ring-marca-600' => $yaElegido,
                        'shadow-sm ring-1 ring-slate-200/70 hover:shadow-md' => ! $yaElegido,
                    ])>
                        @if ($yaElegido)
                            <span class="absolute top-0 left-0 rounded-br bg-marca-600 px-3 py-0.5 text-[11px] font-bold text-white uppercase">Seleccionado</span>
                        @endif
                        <div class="flex items-center gap-4">
                            <div class="flex size-20 shrink-0 items-center justify-center overflow-hidden rounded bg-slate-100 p-2 sm:size-24">
                                @if ($url = $producto->imagenPrincipalUrl())
                                    <img src="{{ $url }}" alt="" class="h-full w-full object-contain mix-blend-multiply transition duration-300 group-hover:scale-105">
                                @else
                                    <x-icono :nombre="$producto->categoria->icono" class="size-10 text-slate-300 transition duration-300 group-hover:scale-105 group-hover:text-marca-500" stroke-width="1.25" />
                                @endif
                            </div>
                            <div class="flex min-w-0 flex-col gap-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                                    <span @class(['rounded px-2 py-0.5 text-[11px] font-bold', 'bg-amber-100 text-amber-800' => $producto->stockBajo(), 'bg-emerald-100 text-emerald-800' => ! $producto->stockBajo()])>
                                        {{ $producto->disponible() }} {{ Str::plural('disponible', $producto->disponible()) }}{{ $producto->stockBajo() ? ' (últimas)' : '' }}
                                    </span>
                                    <span class="font-semibold text-marca-600">{{ $producto->consumo_watts }} W</span>
                                </div>
                                <a href="{{ route('tienda.producto', $producto->slug) }}" target="_blank" class="font-display font-bold text-slate-900 group-hover:text-marca-600 sm:text-lg">{{ $producto->nombre }}</a>
                                <p class="text-xs text-slate-500">{{ $producto->especificacion()?->resumen() }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center justify-between gap-2 md:w-44 md:flex-col md:items-end">
                            <div class="flex flex-col md:text-right">
                                <span class="text-[11px] text-slate-500 uppercase">Precio con IVA</span>
                                <span @class(['font-display text-xl font-extrabold', 'text-marca-600' => $yaElegido, 'text-slate-900' => ! $yaElegido])>Q{{ number_format($producto->precio, 2) }}</span>
                            </div>
                            @if ($yaElegido)
                                <span class="flex items-center gap-1.5 rounded bg-marca-600 px-4 py-2.5 text-sm font-bold text-white">✓ Seleccionado</span>
                            @else
                                <button type="button" wire:click="elegir({{ $producto->id }})" class="rounded bg-black px-4 py-2.5 text-sm font-bold text-white transition hover:bg-marca-600">+ Elegir</button>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="rounded-lg bg-white py-12 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-200/70">No hay productos compatibles y disponibles para este paso.</li>
                @endforelse
            </ul>
        </section>

        {{-- Resumen --}}
        <aside class="flex flex-col gap-4 lg:sticky lg:top-44 lg:col-span-4" id="resumen">
            <section class="flex flex-col gap-4 rounded-lg bg-white p-4 shadow-lg ring-1 ring-slate-200/70">
                <div class="flex flex-col gap-2 rounded bg-slate-100/80 p-3">
                    <div class="flex items-center justify-between">
                        <h2 class="flex items-center gap-2 font-display text-lg font-bold text-slate-900"><x-icono nombre="monitor" class="size-5.5 text-marca-600" /> Tu configuración</h2>
                        <span class="rounded bg-white px-2 py-0.5 text-xs font-bold text-marca-600 shadow-sm">{{ $elegidos }} / {{ count($pasos) }}</span>
                    </div>
                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-200"><div class="h-full rounded-full bg-marca-600 transition-all duration-500" style="width: {{ round($elegidos / count($pasos) * 100) }}%"></div></div>
                </div>

                <ul class="flex flex-col gap-1.5">
                    @foreach ($pasos as $indice => $paso)
                        @php
                            $lineas = $seleccionado->de($paso);
                            $obligatorio = in_array($paso, Configurador::OBLIGATORIOS, true);
                            $numero = str_pad((string) ($indice + 1), 2, '0', STR_PAD_LEFT);
                        @endphp
                        @forelse ($lineas as $linea)
                            <li @class(['flex items-center justify-between gap-2 rounded p-2', 'bg-marca-500/10' => $pasoActual === $paso, 'bg-slate-100/80' => $pasoActual !== $paso])>
                                <button type="button" wire:click="irA('{{ $paso->value }}')" class="flex min-w-0 flex-col text-left">
                                    <span @class(['text-[11px]', 'font-bold text-marca-600' => $pasoActual === $paso, 'text-slate-500' => $pasoActual !== $paso])>{{ $numero }}. {{ $codigos[$paso->value] ?? '' }} · {{ $linea['producto']->consumo_watts * $linea['cantidad'] }} W</span>
                                    <span class="truncate text-xs font-bold text-slate-900">{{ $linea['producto']->nombre }}</span>
                                    <span class="text-[11px] text-slate-500">Q{{ number_format($linea['producto']->precio * $linea['cantidad'], 2) }}</span>
                                </button>
                                <span class="flex shrink-0 items-center gap-1">
                                    @if (Configurador::maximo($paso) > 1)
                                        <select wire:change="cambiarCantidad('{{ $paso->value }}', {{ $linea['producto']->id }}, $event.target.value)" aria-label="Cantidad" class="rounded border-slate-300 py-0 pr-6 pl-1.5 text-xs">
                                            @for ($i = 1; $i <= Configurador::maximo($paso); $i++)
                                                <option value="{{ $i }}" @selected($linea['cantidad'] === $i)>{{ $i }}×</option>
                                            @endfor
                                        </select>
                                    @endif
                                    <button type="button" wire:click="quitar('{{ $paso->value }}', {{ $linea['producto']->id }})" class="flex size-7 items-center justify-center rounded text-slate-400 transition hover:bg-red-50 hover:text-red-600" aria-label="Quitar {{ $linea['producto']->nombre }}"><x-icono nombre="cerrar" class="size-4" /></button>
                                </span>
                            </li>
                        @empty
                            <li>
                                <button type="button" wire:click="irA('{{ $paso->value }}')" class="group flex w-full items-center justify-between rounded bg-slate-200/50 p-2 text-left transition hover:bg-slate-200">
                                    <span class="flex items-center gap-2">
                                        <span @class(['size-2 rounded-full', 'bg-red-500' => $obligatorio, 'bg-slate-400' => ! $obligatorio])></span>
                                        <span class="flex flex-col">
                                            <span class="text-xs font-semibold text-slate-800 group-hover:text-marca-600">{{ $numero }}. {{ $paso->etiqueta() }}</span>
                                            <span @class(['text-[10px] font-bold uppercase', 'text-red-600' => $obligatorio, 'font-medium text-slate-500' => ! $obligatorio])>{{ $obligatorio ? 'Falta · Obligatorio' : 'Opcional' }}</span>
                                        </span>
                                    </span>
                                    <span class="text-xs font-bold text-marca-600 group-hover:underline">Elegir →</span>
                                </button>
                            </li>
                        @endforelse
                    @endforeach
                </ul>

                {{-- Energía --}}
                <div class="flex flex-col gap-1.5 rounded bg-slate-100/80 p-3 text-sm">
                    <div class="flex items-center justify-between"><span class="font-semibold text-slate-800">⚡ Consumo estimado</span><span class="font-bold text-slate-900">{{ $consumo }} W</span></div>
                    <div class="flex items-center justify-between text-slate-600"><span>Fuente recomendada</span><span @class(['font-bold', 'text-emerald-700' => $fuenteRecomendada, 'text-slate-400' => ! $fuenteRecomendada])>{{ $fuenteRecomendada ? $fuenteRecomendada.' W o más' : '—' }}</span></div>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-200" title="Consumo frente a la fuente recomendada"><div class="h-full rounded-full bg-marca-600" style="width: {{ $carga }}%"></div></div>
                </div>

                {{-- Compatibilidad --}}
                <div class="flex flex-col gap-2" aria-live="polite">
                    @foreach ($hallazgos as $hallazgo)
                        <div @class(['flex items-start gap-2 rounded p-3 text-sm', 'bg-red-50 text-red-900' => $hallazgo->esError(), 'bg-amber-50 text-amber-900' => ! $hallazgo->esError()])>
                            <span @class(['mt-0.5 font-bold', 'text-red-600' => $hallazgo->esError(), 'text-amber-600' => ! $hallazgo->esError()])>{{ $hallazgo->esError() ? '✕' : '!' }}</span>
                            <div>
                                <p class="font-bold">{{ $hallazgo->titulo }} <span class="text-[11px] font-normal opacity-60">({{ $hallazgo->codigo }})</span></p>
                                <p class="text-xs leading-snug">{{ $hallazgo->mensaje }}</p>
                            </div>
                        </div>
                    @endforeach
                    @foreach ($sinExistencia as $producto)
                        <p class="rounded bg-red-50 p-3 text-sm text-red-900"><span class="font-bold text-red-600">✕</span> {{ $producto->nombre }} ya no tiene existencia suficiente.</p>
                    @endforeach
                    @if ($hallazgos === [] && ! $seleccionado->vacia())
                        <p class="rounded bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">✓ Todas las piezas son compatibles.</p>
                    @endif
                </div>

                {{-- Totales --}}
                <div class="flex flex-col gap-2 text-sm">
                    <div class="flex items-center justify-between text-slate-600"><span>Componentes ({{ $piezas }} {{ Str::plural('pieza', $piezas) }})</span><span class="font-semibold text-slate-900">Q{{ number_format($totalComponentes, 2) }}</span></div>
                    <label class="flex cursor-pointer items-start gap-2.5 rounded bg-slate-100/80 p-2.5 transition hover:bg-slate-200/70">
                        <input type="checkbox" wire:model.live="incluyeArmado" class="mt-0.5 rounded border-slate-300 text-marca-600 focus:ring-marca-600">
                        <span class="flex flex-1 flex-col">
                            <span class="flex items-center justify-between gap-2"><span class="font-bold text-slate-900">Armado y pruebas en taller</span><span class="font-bold text-marca-600">+ Q{{ number_format($costoArmado > 0 ? $costoArmado : \App\Models\ParametroSistema::valor('costo_servicio_armado', 250), 2) }}</span></span>
                            <span class="text-[11px] text-slate-500">Un técnico arma tu PC, ordena el cableado y la prueba (encendido, temperatura y estabilidad) antes de entregártela.</span>
                        </span>
                    </label>
                    <div class="flex items-end justify-between pt-1">
                        <span class="flex flex-col"><span class="text-[11px] font-semibold text-slate-500 uppercase">Total</span><span class="text-[11px] text-emerald-700">IVA incluido</span></span>
                        <span class="font-display text-2xl font-extrabold text-slate-900">Q{{ number_format($total, 2) }}</span>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    @include('tienda.partials.configuracion-al-carrito', ['puedeComprarse' => $puedeComprarse])
                    @unless ($puedeComprarse)
                        <p class="text-center text-[11px] text-slate-500">Completa los componentes obligatorios y corrige los errores para agregarla.</p>
                    @endunless
                </div>

                <div class="flex flex-col gap-1.5 border-t border-slate-100 pt-3">
                    <span class="text-[11px] font-semibold tracking-wider text-slate-500 uppercase">Guardar o compartir</span>
                    <div class="flex gap-2">
                        <input type="text" wire:model="nombre" maxlength="80" placeholder="Ej. Mi PC gamer" aria-label="Nombre de la configuración" class="min-w-0 flex-1 rounded border-0 bg-slate-100 text-sm focus:ring-1 focus:ring-marca-600">
                        <button type="button" wire:click="guardar" class="shrink-0 rounded bg-black px-4 text-sm font-bold text-white transition hover:bg-marca-600">Guardar</button>
                    </div>
                    @error('nombre') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @guest <p class="text-xs text-slate-500">Inicia sesión para guardar y compartir tu configuración.</p> @endguest

                    @if ($codigoGuardado)
                        <div x-data="{ copiado: false }" class="rounded bg-slate-100 p-2 text-xs">
                            <p class="text-slate-500">Enlace para compartir:</p>
                            <div class="mt-1 flex gap-2">
                                <input type="text" readonly value="{{ route('tienda.configurador.ver', $codigoGuardado) }}" aria-label="Enlace para compartir" class="min-w-0 flex-1 rounded border-slate-200 bg-white text-xs" x-ref="enlace">
                                <button type="button" @click="navigator.clipboard.writeText($refs.enlace.value); copiado = true; setTimeout(() => copiado = false, 2000)" class="font-semibold text-marca-600" x-text="copiado ? 'Copiado' : 'Copiar'"></button>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <details class="rounded-lg bg-white p-4 text-sm shadow-sm ring-1 ring-slate-200/70">
                <summary class="flex cursor-pointer items-center gap-2 font-semibold text-slate-900"><x-icono nombre="escudo" class="size-5 text-marca-600" /> ¿Qué revisa el configurador? (18 reglas)</summary>
                <ol class="mt-3 space-y-1 text-xs text-slate-600">
                    @foreach ($reglas as $codigo => $texto)
                        <li><span class="font-mono text-slate-400">{{ $codigo }}</span> {{ $texto }}</li>
                    @endforeach
                </ol>
            </details>
        </aside>
    </div>

    {{-- Barra inferior en teléfono --}}
    <div class="fixed inset-x-0 bottom-0 z-30 flex items-center justify-between gap-3 bg-marca-800 px-4 py-3 text-white shadow-[0_-4px_20px_rgba(0,0,0,0.25)] lg:hidden">
        <span class="flex flex-col leading-tight">
            <span class="text-[10px] font-bold tracking-wide text-marca-300 uppercase">{{ $elegidos }} / {{ count($pasos) }} · Total</span>
            <span class="font-display text-lg font-bold text-cian-400">Q{{ number_format($total, 2) }}</span>
        </span>
        <a href="#resumen" class="rounded-lg bg-linear-to-r from-acento-500 to-acento-600 px-4 py-2.5 text-sm font-bold">Ver resumen</a>
    </div>
</div>
