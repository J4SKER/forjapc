<x-app-layout :title="$producto->nombre">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <nav class="text-xs text-slate-500" aria-label="Ruta">
            <a href="{{ route('inicio') }}" class="hover:text-marca-600">Inicio</a>
            <span class="mx-1">/</span>
            <a href="{{ route('tienda.categoria', $producto->categoria->slug) }}" class="hover:text-marca-600">{{ $producto->categoria->nombre }}</a>
            <span class="mx-1">/</span>
            <span class="text-slate-700">{{ $producto->nombre }}</span>
        </nav>

        <div class="mt-4 grid gap-8 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-8 lg:grid-cols-2">
            {{-- Galería --}}
            <div x-data="{ actual: 0 }">
                <div class="flex aspect-square items-center justify-center overflow-hidden rounded-xl bg-slate-50 p-6">
                    @forelse ($producto->imagenes as $indice => $imagen)
                        <img x-show="actual === {{ $indice }}" @if ($indice > 0) x-cloak @endif src="{{ $imagen->url() }}" alt="{{ $producto->nombre }}" class="h-full w-full object-contain">
                    @empty
                        <x-icono :nombre="$producto->categoria->icono" class="size-48 text-slate-300" stroke-width="0.8" />
                    @endforelse
                </div>
                @if ($producto->imagenes->count() > 1)
                    <div class="mt-3 flex gap-2 overflow-x-auto">
                        @foreach ($producto->imagenes as $indice => $imagen)
                            <button type="button" @click="actual = {{ $indice }}" class="size-16 shrink-0 overflow-hidden rounded-lg border-2 bg-slate-50 p-1"
                                    :class="actual === {{ $indice }} ? 'border-marca-500' : 'border-transparent'" aria-label="Imagen {{ $indice + 1 }}">
                                <img src="{{ $imagen->url() }}" alt="" class="h-full w-full object-contain">
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Datos de compra --}}
            <div>
                <p class="text-sm font-semibold tracking-wide text-marca-600 uppercase">{{ $producto->marca->nombre }}</p>
                <h1 class="mt-1 text-2xl font-extrabold text-slate-900 sm:text-3xl">{{ $producto->nombre }}</h1>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span>SKU: {{ $producto->sku }}</span>
                    @if ($calificacion)
                        <span class="flex items-center gap-1 text-amber-600">
                            @for ($i = 1; $i <= 5; $i++)<span aria-hidden="true">{{ $i <= round($calificacion) ? '★' : '☆' }}</span>@endfor
                            <span class="text-slate-500">{{ $calificacion }} ({{ $producto->resenasAprobadas->count() }})</span>
                        </span>
                    @endif
                </div>

                <div class="mt-5 rounded-xl bg-slate-50 p-4">
                    @if ($producto->enOferta())
                        <p class="text-sm text-slate-500"><span class="line-through">Q{{ number_format($producto->precio_anterior, 2) }}</span>
                            <span class="ml-2 rounded bg-acento-600 px-1.5 py-0.5 text-xs font-bold text-white">Ahorras {{ $producto->porcentajeDescuento() }}%</span></p>
                    @endif
                    <p class="text-3xl font-extrabold text-slate-900">Q{{ number_format($producto->precio, 2) }}</p>
                    <p class="text-xs text-slate-500">Precio con IVA incluido</p>

                    <p @class(['mt-3 flex items-center gap-2 text-sm font-semibold', 'text-emerald-700' => $producto->estaDisponible(), 'text-slate-500' => ! $producto->estaDisponible()])>
                        <span @class(['size-2.5 rounded-full', 'bg-emerald-500' => $producto->estaDisponible(), 'bg-slate-400' => ! $producto->estaDisponible()])></span>
                        @if ($producto->estaDisponible())
                            {{ $producto->stockBajo() ? '¡Últimas '.$producto->disponible().' unidades!' : 'Disponible para entrega inmediata' }}
                        @else
                            Sin existencia por el momento
                        @endif
                    </p>

                    @include('tienda.partials.agregar-al-carrito', ['producto' => $producto])
                </div>

                <ul class="mt-5 space-y-2 text-sm text-slate-700">
                    <li class="flex items-center gap-2"><x-icono nombre="escudo" class="size-5 text-marca-600" /> Garantía de {{ $producto->garantia_meses >= 12 && $producto->garantia_meses % 12 === 0 ? ($producto->garantia_meses / 12).' '.Str::plural('año', $producto->garantia_meses / 12) : $producto->garantia_meses.' meses' }}</li>
                    <li class="flex items-center gap-2"><x-icono nombre="camion" class="size-5 text-marca-600" /> Envío a los 22 departamentos o recoge en tienda</li>
                    <li class="flex items-center gap-2"><x-icono nombre="tarjeta" class="size-5 text-marca-600" /> Paga con PayPal, tarjeta o transferencia</li>
                    @if ($producto->tipoComponente())
                        <li class="flex items-center gap-2"><x-icono nombre="herramienta" class="size-5 text-marca-600" />
                            <a href="{{ route('tienda.configurador') }}" class="text-marca-700 underline">Úsalo en el configurador para armar tu PC</a></li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            {{-- Especificaciones y descripción --}}
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:col-span-2">
                <h2 class="text-lg font-bold text-slate-900">Especificaciones</h2>
                @if ($especificacion)
                    <dl class="mt-4 divide-y divide-slate-100 text-sm">
                        @foreach ($especificacion->filas() as $etiqueta => $valor)
                            <div class="grid grid-cols-2 gap-4 py-2.5">
                                <dt class="text-slate-500">{{ $etiqueta }}</dt>
                                <dd class="font-medium text-slate-800">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
                @if ($producto->descripcion)
                    <h3 class="mt-6 font-semibold text-slate-900">Descripción</h3>
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line text-slate-600">{{ $producto->descripcion }}</p>
                @endif
            </section>

            {{-- Reseñas --}}
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200" id="resenas">
                <h2 class="text-lg font-bold text-slate-900">Opiniones</h2>
                @forelse ($producto->resenasAprobadas as $resena)
                    <article class="mt-4 border-t border-slate-100 pt-4 first-of-type:border-0 first-of-type:pt-0">
                        <p class="text-amber-600" aria-label="{{ $resena->calificacion }} de 5">{{ str_repeat('★', $resena->calificacion) }}{{ str_repeat('☆', 5 - $resena->calificacion) }}</p>
                        <h3 class="font-semibold text-slate-900">{{ $resena->titulo }}</h3>
                        <p class="mt-1 text-sm text-slate-600">{{ $resena->comentario }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ Str::before($resena->usuario->nombre, ' ') }} · {{ $resena->created_at->translatedFormat('j M Y') }}
                            @if ($resena->compra_verificada) · <span class="text-emerald-700">Compra verificada</span>@endif</p>
                    </article>
                @empty
                    <p class="mt-3 text-sm text-slate-500">Aún no hay opiniones de este producto.</p>
                @endforelse

                @include('tienda.partials.formulario-resena', ['producto' => $producto])
            </section>
        </div>

        @if ($relacionados->isNotEmpty())
            <section class="mt-10">
                <h2 class="mb-4 text-xl font-bold text-slate-900">También te puede interesar</h2>
                <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
                    @foreach ($relacionados as $relacionado)
                        <x-producto-card :producto="$relacionado" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
