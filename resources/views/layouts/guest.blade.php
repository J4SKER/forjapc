<x-app-layout>
    <div class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
        <div class="grid grid-cols-1 overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-200/70 lg:min-h-[640px] lg:grid-cols-12">
            {{-- Panel de la marca --}}
            <div class="relative hidden flex-col justify-between overflow-hidden bg-marca-800 p-10 text-white lg:col-span-5 lg:flex">
                <svg class="pointer-events-none absolute inset-0 size-full opacity-10" aria-hidden="true">
                    <defs>
                        <pattern id="circuito" width="36" height="36" patternUnits="userSpaceOnUse">
                            <path d="M 36 0 L 0 0 0 36" fill="none" stroke="#4cd7f6" stroke-width="0.8" />
                            <circle cx="18" cy="18" r="1.5" fill="#dbe1ff" />
                            <path d="M 0 18 h 8 m 20 0 h 8 M 18 0 v 8 m 0 20 v 8" stroke="#316bf3" stroke-width="0.75" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#circuito)" />
                </svg>
                <div class="pointer-events-none absolute -top-24 -left-24 size-80 rounded-full bg-marca-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -right-24 -bottom-24 size-72 rounded-full bg-cian-400/15 blur-3xl"></div>

                <div class="relative flex flex-col gap-6">
                    <span class="inline-flex w-fit items-center gap-1.5 rounded bg-marca-500 px-2.5 py-1 text-[11px] font-bold tracking-wider uppercase shadow-sm">
                        <span class="size-1.5 animate-pulse rounded-full bg-cian-300 motion-reduce:animate-none"></span> Hardware GT
                    </span>
                    <div class="flex flex-col gap-2">
                        <h2 class="font-display text-3xl leading-tight font-bold tracking-tight">Tu tienda de hardware y PCs a la medida</h2>
                        <p class="text-sm leading-relaxed text-marca-200">Con tu cuenta sigues tus pedidos, guardas tus configuraciones y compras más rápido.</p>
                    </div>
                    <ul class="flex flex-col gap-3 pt-2">
                        @foreach ([
                            ['camion', 'Seguimiento de pedidos', 'Ve cada etapa: pago, armado, pruebas y envío a los 22 departamentos.'],
                            ['cpu', 'Configuraciones guardadas', 'Guarda tus PCs del configurador y compártelas con un enlace.'],
                            ['tarjeta', 'Compra más rápida', 'Tus direcciones y tu NIT quedan guardados para el próximo pedido.'],
                        ] as [$icono, $titulo, $texto])
                            <li class="flex items-start gap-4 rounded-lg bg-white/5 p-3.5">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-marca-500/25 text-cian-300 shadow-inner"><x-icono :nombre="$icono" class="size-5.5" /></span>
                                <span>
                                    <span class="block font-display font-semibold">{{ $titulo }}</span>
                                    <span class="block text-xs text-marca-200">{{ $texto }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative flex items-center gap-2 pt-8 text-xs text-marca-200"><x-icono nombre="escudo" class="size-4.5 text-cian-400" /> Tus datos se envían cifrados y tu contraseña se guarda protegida.</p>
            </div>

            {{-- Formulario --}}
            <div class="flex flex-col justify-center p-6 sm:p-10 lg:col-span-7 lg:p-14">
                <div class="mx-auto w-full max-w-md">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
