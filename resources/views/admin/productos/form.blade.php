<x-admin-layout :title="$producto->exists ? 'Editar producto' : 'Nuevo producto'">
    @php
        $tipoActual = $producto->exists ? $producto->tipoComponente()?->value : null;
    @endphp

    <a href="{{ route('admin.productos.index') }}" class="text-sm text-marca-600 hover:underline">&larr; Volver a productos</a>

    <form method="POST" enctype="multipart/form-data"
          action="{{ $producto->exists ? route('admin.productos.update', $producto) : route('admin.productos.store') }}"
          x-data="{ categoria: '{{ old('categoria_id', $producto->categoria_id) }}', tipos: @js($tiposPorCategoria), get tipo() { return this.tipos[this.categoria] ?? null } }"
          class="mt-4 grid grid-cols-1 items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        @csrf
        @if ($producto->exists)
            @method('PUT')
        @endif

        <div class="space-y-6">
            <section class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="flex items-center gap-2.5 border-b border-slate-100 pb-3 font-display text-lg font-bold text-slate-900"><span class="flex size-7 items-center justify-center rounded-lg bg-marca-800 text-xs font-bold text-cian-400">1</span>Datos generales</h2>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="categoria_id" value="Categoría" />
                        <select id="categoria_id" name="categoria_id" x-model="categoria" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm">
                            <option value="">Selecciona…</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('categoria_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="marca_id" value="Marca" />
                        <select id="marca_id" name="marca_id" required class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm">
                            <option value="">Selecciona…</option>
                            @foreach ($marcas as $marca)
                                <option value="{{ $marca->id }}" @selected((int) old('marca_id', $producto->marca_id) === $marca->id)>{{ $marca->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('marca_id')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="nombre" value="Nombre" />
                    <x-text-input id="nombre" name="nombre" class="mt-1 block w-full" :value="old('nombre', $producto->nombre)" required maxlength="160" />
                    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <x-input-label for="sku" value="SKU" />
                        <x-text-input id="sku" name="sku" class="mt-1 block w-full uppercase" :value="old('sku', $producto->sku)" required maxlength="40" />
                        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="slug" value="Dirección web (se genera del nombre)" />
                        <x-text-input id="slug" name="slug" class="mt-1 block w-full" :value="old('slug', $producto->slug)" maxlength="180" />
                        <x-input-error :messages="$errors->get('slug')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="descripcion" value="Descripción" />
                    <textarea id="descripcion" name="descripcion" rows="4" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm">{{ old('descripcion', $producto->descripcion) }}</textarea>
                    <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                </div>
            </section>

            {{-- Especificaciones técnicas según el tipo de componente --}}
            @foreach ($campos as $tipo => $definiciones)
                <fieldset x-show="tipo === '{{ $tipo }}'" x-cloak :disabled="tipo !== '{{ $tipo }}'" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                    <legend class="sr-only">Especificaciones técnicas</legend>
                    <h2 class="flex items-center gap-2.5 border-b border-slate-100 pb-3 font-display text-lg font-bold text-slate-900"><span class="flex size-7 items-center justify-center rounded-lg bg-marca-800 text-xs font-bold text-cian-400">2</span>Especificaciones técnicas · {{ \App\Enums\TipoComponente::from($tipo)->etiqueta() }}</h2>
                    <p class="text-xs text-slate-500">El configurador de PC usa estos datos para validar la compatibilidad.</p>

                    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($definiciones as $campo => $definicion)
                            @php
                                $valor = old("esp.{$campo}", $tipoActual === $tipo ? ($especificacion[$campo] ?? null) : null);
                                $id = "esp-{$tipo}-{$campo}";
                            @endphp
                            <div @class(['sm:col-span-2 lg:col-span-3' => $definicion['tipo'] === 'sockets'])>
                                @if ($definicion['tipo'] === 'booleano')
                                    <label class="mt-6 flex items-center gap-2 text-sm text-slate-700">
                                        <input type="hidden" name="esp[{{ $campo }}]" value="0">
                                        <input type="checkbox" name="esp[{{ $campo }}]" value="1" @checked($valor) class="rounded border-slate-300 text-marca-600">
                                        {{ $definicion['etiqueta'] }}
                                    </label>
                                @elseif ($definicion['tipo'] === 'sockets')
                                    <span class="block text-sm font-medium text-slate-700">{{ $definicion['etiqueta'] }}</span>
                                    <div class="mt-2 flex flex-wrap gap-4">
                                        @foreach ($sockets as $socket)
                                            <label class="flex items-center gap-2 text-sm">
                                                <input type="checkbox" name="esp[sockets][]" value="{{ $socket->id }}" @checked(in_array($socket->id, (array) $valor)) class="rounded border-slate-300 text-marca-600">
                                                {{ $socket->nombre }}
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <x-input-label :for="$id" :value="$definicion['etiqueta']" />
                                    @if ($definicion['tipo'] === 'socket')
                                        <select id="{{ $id }}" name="esp[{{ $campo }}]" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                            <option value="">Selecciona…</option>
                                            @foreach ($sockets as $socket)
                                                <option value="{{ $socket->id }}" @selected((int) $valor === $socket->id)>{{ $socket->nombre }}</option>
                                            @endforeach
                                        </select>
                                    @elseif ($definicion['tipo'] === 'opcion')
                                        <select id="{{ $id }}" name="esp[{{ $campo }}]" class="mt-1 block w-full rounded-lg border-slate-300 text-sm shadow-sm">
                                            <option value="">Selecciona…</option>
                                            @foreach ($definicion['opciones'] as $opcion)
                                                <option value="{{ $opcion }}" @selected((string) $valor === $opcion)>{{ $opcion }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <x-text-input :id="$id" name="esp[{{ $campo }}]" class="mt-1 block w-full" :value="$valor"
                                                      :type="in_array($definicion['tipo'], ['entero', 'decimal']) ? 'number' : 'text'"
                                                      :step="$definicion['tipo'] === 'decimal' ? '0.1' : '1'" />
                                    @endif
                                @endif
                                <x-input-error :messages="$errors->get('esp.'.$campo)" class="mt-1" />
                            </div>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            {{-- Imágenes --}}
            <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="flex items-center gap-2.5 border-b border-slate-100 pb-3 font-display text-lg font-bold text-slate-900"><span class="flex size-7 items-center justify-center rounded-lg bg-marca-800 text-xs font-bold text-cian-400">3</span>Imágenes</h2>
                @if ($producto->exists && $producto->imagenes->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach ($producto->imagenes as $imagen)
                            <div class="relative size-28 overflow-hidden rounded-lg bg-slate-50 p-1 ring-1 ring-slate-200">
                                <img src="{{ $imagen->url() }}" alt="" class="h-full w-full object-contain">
                                <button type="submit" form="eliminar-imagen-{{ $imagen->id }}" class="absolute top-1 right-1 rounded-full bg-white/90 p-1 text-acento-700 shadow hover:bg-white" aria-label="Eliminar imagen">
                                    <x-icono nombre="cerrar" class="size-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <input type="file" name="imagenes[]" multiple accept="image/jpeg,image/png,image/webp" aria-label="Subir imágenes"
                       class="mt-4 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-marca-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-marca-700">
                <p class="mt-1 text-xs text-slate-500">JPG, PNG o WebP, máximo 2 MB cada una. La primera es la principal.</p>
                <x-input-error :messages="$errors->get('imagenes.*')" class="mt-1" />
            </section>
        </div>

        {{-- Precio y publicación --}}
        <aside class="space-y-6 xl:sticky xl:top-24">
            <section class="space-y-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70">
                <h2 class="flex items-center gap-2.5 border-b border-slate-100 pb-3 font-display text-lg font-bold text-slate-900"><span class="flex size-7 items-center justify-center rounded-lg bg-marca-800 text-xs font-bold text-cian-400">4</span>Precio y publicación</h2>
                <div>
                    <x-input-label for="precio" value="Precio (Q, IVA incluido)" />
                    <x-text-input id="precio" name="precio" type="number" step="0.01" min="0.01" class="mt-1 block w-full" :value="old('precio', $producto->precio)" required />
                    <x-input-error :messages="$errors->get('precio')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="precio_anterior" value="Precio anterior (opcional, para mostrar oferta)" />
                    <x-text-input id="precio_anterior" name="precio_anterior" type="number" step="0.01" class="mt-1 block w-full" :value="old('precio_anterior', $producto->precio_anterior)" />
                    <x-input-error :messages="$errors->get('precio_anterior')" class="mt-2" />
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="garantia_meses" value="Garantía (meses)" />
                        <x-text-input id="garantia_meses" name="garantia_meses" type="number" min="0" class="mt-1 block w-full" :value="old('garantia_meses', $producto->garantia_meses)" required />
                    </div>
                    <div>
                        <x-input-label for="stock_minimo" value="Stock mínimo" />
                        <x-text-input id="stock_minimo" name="stock_minimo" type="number" min="0" class="mt-1 block w-full" :value="old('stock_minimo', $producto->stock_minimo)" required />
                    </div>
                    <div>
                        <x-input-label for="consumo_watts" value="Consumo (W)" />
                        <x-text-input id="consumo_watts" name="consumo_watts" type="number" min="0" class="mt-1 block w-full" :value="old('consumo_watts', $producto->consumo_watts)" required />
                    </div>
                </div>
                <x-input-error :messages="array_merge($errors->get('garantia_meses'), $errors->get('stock_minimo'), $errors->get('consumo_watts'))" />

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="activo" value="0">
                    <input type="checkbox" name="activo" value="1" @checked(old('activo', $producto->activo)) class="rounded border-slate-300 text-marca-600"> Visible en la tienda
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="destacado" value="0">
                    <input type="checkbox" name="destacado" value="1" @checked(old('destacado', $producto->destacado)) class="rounded border-slate-300 text-marca-600"> Destacado en el inicio
                </label>

                <x-primary-button class="w-full">{{ $producto->exists ? 'Guardar cambios' : 'Crear producto' }}</x-primary-button>
            </section>

            @if ($producto->exists)
                <section class="rounded-xl bg-white p-6 text-sm shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="flex items-center gap-2.5 border-b border-slate-100 pb-3 font-display text-lg font-bold text-slate-900"><span class="flex size-7 items-center justify-center rounded-lg bg-marca-800 text-xs font-bold text-cian-400">5</span>Inventario</h2>
                    <dl class="mt-3 grid grid-cols-2 gap-y-1.5">
                        <dt class="text-slate-500">Existencia</dt><dd class="text-right font-semibold">{{ $producto->existencia }}</dd>
                        <dt class="text-slate-500">Reservado</dt><dd class="text-right">{{ $producto->reservado }}</dd>
                        <dt class="text-slate-500">Disponible</dt><dd class="text-right">{{ $producto->disponible() }}</dd>
                        <dt class="text-slate-500">Costo promedio</dt><dd class="text-right">Q{{ number_format($producto->costo_promedio, 2) }}</dd>
                    </dl>
                    <p class="mt-3 text-xs text-slate-500">La existencia solo cambia con compras, ventas o ajustes registrados en el kardex.</p>
                    <div class="mt-3 flex gap-3">
                        <a href="{{ route('admin.inventario.kardex', $producto) }}" class="font-semibold text-marca-600 hover:underline">Ver kardex</a>
                        <a href="{{ route('tienda.producto', $producto->slug) }}" target="_blank" class="font-semibold text-slate-600 hover:underline">Ver en tienda</a>
                    </div>
                </section>
            @endif
        </aside>
    </form>

    @if ($producto->exists)
        @foreach ($producto->imagenes as $imagen)
            <form id="eliminar-imagen-{{ $imagen->id }}" method="POST" action="{{ route('admin.productos.imagenes.destroy', [$producto, $imagen]) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <form method="POST" action="{{ route('admin.productos.destroy', $producto) }}" class="mt-6" onsubmit="return confirm('¿Eliminar este producto del catálogo? Su historial se conserva.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm font-semibold text-acento-700 hover:underline">Eliminar producto del catálogo</button>
        </form>
    @endif
</x-admin-layout>
