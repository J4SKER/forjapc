<footer class="mt-12 bg-marca-900 text-sm text-marca-100">
    {{-- Beneficios --}}
    <div class="border-b border-marca-800">
        <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-8 sm:px-6 lg:grid-cols-4 lg:px-8">
            @foreach ([
                ['camion', 'Envío a todo el país', 'Entrega en los 22 departamentos'],
                ['escudo', 'Garantía real', 'Productos nuevos con garantía'],
                ['tarjeta', 'Pago seguro', 'PayPal, tarjeta o transferencia'],
                ['herramienta', 'Armado y pruebas', 'Tu PC lista para usar'],
            ] as [$icono, $titulo, $texto])
                <div class="flex items-start gap-3">
                    <span class="rounded-lg bg-marca-800 p-2 text-acento-500"><x-icono :nombre="$icono" class="size-6" /></span>
                    <div>
                        <p class="font-semibold text-white">{{ $titulo }}</p>
                        <p class="text-xs">{{ $texto }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
        <div>
            <a href="{{ route('inicio') }}" class="flex items-center gap-2 text-white">
                <x-application-logo class="size-8" />
                <span class="text-xl font-extrabold">Forja<span class="text-acento-500">PC</span></span>
            </a>
            <p class="mt-3 leading-relaxed">Componentes de cómputo, monitores, periféricos y computadoras armadas a la medida en Ciudad de Guatemala.</p>
        </div>

        <div>
            <p class="mb-3 font-semibold text-white">Categorías</p>
            <ul class="space-y-2">
                @foreach (array_slice(config('tienda.categorias'), 0, 6) as $categoria)
                    <li><a href="{{ route('tienda.categoria', $categoria['slug']) }}" class="hover:text-white">{{ $categoria['nombre'] }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <p class="mb-3 font-semibold text-white">Mi cuenta</p>
            <ul class="space-y-2">
                <li><a href="{{ route('dashboard') }}" class="hover:text-white">Mis pedidos</a></li>
                <li><a href="{{ route('tienda.configurador') }}" class="hover:text-white">Arma tu PC</a></li>
                <li><a href="{{ route('carrito') }}" class="hover:text-white">Carrito</a></li>
                @guest
                    <li><a href="{{ route('register') }}" class="hover:text-white">Crear cuenta</a></li>
                @endguest
            </ul>
        </div>

        <div>
            <p class="mb-3 font-semibold text-white">Contacto</p>
            <ul class="space-y-2">
                <li class="flex items-center gap-2"><x-icono nombre="ubicacion" class="size-4 shrink-0" /> Ciudad de Guatemala</li>
                <li class="flex items-center gap-2"><x-icono nombre="telefono" class="size-4 shrink-0" /> {{ \App\Models\ParametroSistema::valor('telefono_contacto') }}</li>
                <li class="flex items-center gap-2"><x-icono nombre="correo" class="size-4 shrink-0" /> {{ \App\Models\ParametroSistema::valor('correo_contacto') }}</li>
                <li>
                    <a href="https://wa.me/{{ \App\Models\ParametroSistema::valor('whatsapp_contacto') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-1.5 font-medium text-white hover:bg-emerald-500">
                        <x-icono nombre="chat" class="size-4" /> Escríbenos por WhatsApp
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="border-t border-marca-800">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-5 text-xs sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ date('Y') }} ForjaPC. Precios en quetzales, IVA incluido.</p>
            <div class="flex flex-wrap items-center gap-2">
                @foreach (['PayPal', 'Visa', 'Mastercard', 'Transferencia'] as $metodo)
                    <span class="rounded bg-white/10 px-2 py-1 font-semibold text-white">{{ $metodo }}</span>
                @endforeach
            </div>
        </div>
    </div>
</footer>
