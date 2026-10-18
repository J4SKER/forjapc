@use('App\Enums\EstadoPedido')
@use('App\Enums\MetodoPago')
<x-app-layout :title="'Pedido '.$pedido->numero">
    @php
        $etapas = [EstadoPedido::PendientePago, EstadoPedido::Pagado, $pedido->ordenesArmado->isNotEmpty() ? EstadoPedido::EnArmado : EstadoPedido::EnPreparacion, EstadoPedido::ListoEntrega];
        if ($pedido->esEnvio()) {
            $etapas[] = EstadoPedido::Enviado;
        }
        $etapas[] = EstadoPedido::Entregado;
        $alcanzadas = $pedido->historial->keyBy(fn ($r) => $r->estado_nuevo->value);
        $ultima = collect($etapas)->filter(fn ($e) => $alcanzadas->has($e->value))->keys()->last() ?? 0;
        $avance = count($etapas) > 1 ? round($ultima / (count($etapas) - 1) * 100) : 0;
        $porVerificar = $pedido->pagos->firstWhere(fn ($p) => $p->estado === \App\Enums\EstadoPago::Pendiente && $p->comprobante_ruta);
        $cerrado = in_array($pedido->estado, [EstadoPedido::Cancelado, EstadoPedido::Reembolsado], true);
        $iconos = ['PENDIENTE_PAGO' => 'candado', 'PAGADO' => 'tarjeta', 'EN_PREPARACION' => 'paquete', 'EN_ARMADO' => 'herramienta', 'LISTO_ENTREGA' => 'tienda', 'ENVIADO' => 'camion', 'ENTREGADO' => 'escudo'];
        $piezas = $pedido->items->sum('cantidad');
    @endphp

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8" x-data="{ cancelar: false }">
        <nav class="mb-4 flex flex-wrap items-center gap-1.5 text-xs text-slate-500" aria-label="Ruta">
            <a href="{{ route('inicio') }}" class="flex items-center gap-1 hover:text-marca-600"><x-icono nombre="tienda" class="size-4" /> Inicio</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('dashboard') }}" class="hover:text-marca-600">Mi cuenta</a>
            <span class="text-slate-300">/</span>
            <a href="{{ route('pedidos.index') }}" class="hover:text-marca-600">Mis pedidos</a>
            <span class="text-slate-300">/</span>
            <span class="rounded bg-slate-200/70 px-1.5 py-0.5 font-semibold text-marca-700">{{ $pedido->numero }}</span>
        </nav>

        {{-- Encabezado --}}
        <div class="mb-5 flex flex-col justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6 lg:flex-row lg:items-center">
            <div class="flex flex-col gap-1">
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Pedido <span class="text-marca-600">{{ $pedido->numero }}</span></h1>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold tracking-wide uppercase {{ $pedido->estado->color() }}">
                        @if ($pedido->estaPendienteDePago())<span class="size-2 animate-pulse rounded-full bg-current motion-reduce:animate-none"></span>@endif
                        {{ $pedido->estado->etiqueta() }}
                    </span>
                </div>
                <p class="text-sm text-slate-500">Realizado el {{ $pedido->created_at->translatedFormat('j \d\e F \d\e Y · H:i') }} · Pago: {{ $pedido->metodo_pago->etiqueta() }}</p>
            </div>
            @if ($pedido->estaPendienteDePago())
                <button type="button" @click="cancelar = true" class="inline-flex w-fit items-center gap-1.5 rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-200">
                    <x-icono nombre="cerrar" class="size-4.5 text-red-600" /> Cancelar pedido
                </button>
            @endif
        </div>

        {{-- Seguimiento --}}
        @if ($cerrado)
            <p class="mb-5 rounded-xl bg-slate-200/70 px-5 py-4 text-sm text-slate-700">Este pedido está <strong>{{ mb_strtolower($pedido->estado->etiqueta()) }}</strong>. {{ $pedido->motivo_cancelacion }}</p>
        @else
            <section class="mb-5 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="flex items-center gap-2 font-display text-lg font-bold text-slate-900"><x-icono nombre="camion" class="size-5.5 text-marca-600" /> Seguimiento</h2>
                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">Etapa {{ $ultima + 1 }} de {{ count($etapas) }}</span>
                </div>
                <div class="overflow-x-auto py-2">
                    <ol class="relative flex min-w-160 items-start justify-between">
                        <span class="absolute top-5 right-10 left-10 h-1 rounded bg-slate-200"></span>
                        <span class="absolute top-5 left-10 h-1 rounded bg-marca-600 transition-all" style="width: calc(({{ $avance }} / 100) * (100% - 5rem))"></span>
                        @foreach ($etapas as $indice => $etapa)
                            @php
                                $registro = $alcanzadas->get($etapa->value);
                                $actual = $indice === $ultima;
                            @endphp
                            <li class="relative z-10 flex w-28 flex-col items-center gap-1.5 text-center">
                                <span @class([
                                    'flex size-10 items-center justify-center rounded-full',
                                    'bg-marca-600 text-white shadow-md ring-4 ring-marca-600/20' => $actual,
                                    'bg-emerald-600 text-white' => $registro && ! $actual,
                                    'bg-slate-200 text-slate-500' => ! $registro,
                                ])><x-icono :nombre="$iconos[$etapa->value] ?? 'paquete'" class="size-5" /></span>
                                <span @class(['text-[11px] font-bold uppercase', 'text-marca-600' => $actual, 'text-slate-800' => $registro && ! $actual, 'text-slate-400' => ! $registro])>{{ $etapa->etiqueta() }}</span>
                                <span class="text-[11px] leading-tight text-slate-500">{{ $registro ? $registro->created_at->format('d/m H:i') : '—' }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        {{-- Pago pendiente --}}
        @if ($pedido->estaPendienteDePago())
            <section class="mb-5 rounded-xl bg-white p-5 shadow-md ring-1 ring-slate-200/70 sm:p-6">
                <div class="mb-5 flex flex-col justify-between gap-4 rounded-lg bg-marca-800 p-4 text-white md:flex-row md:items-center">
                    <div class="flex items-center gap-4">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-marca-600 shadow-sm"><x-icono nombre="tarjeta" class="size-7" /></span>
                        <div>
                            <span class="text-[11px] font-bold tracking-widest text-cian-400 uppercase">Acción requerida</span>
                            <h2 class="font-display text-xl leading-tight font-bold">Completa tu pago</h2>
                            <p class="text-xs text-marca-300">Los productos están apartados a tu nombre mientras pagas.</p>
                        </div>
                    </div>
                    <div class="flex flex-col rounded-lg bg-white/10 p-3 md:items-end">
                        <span class="text-xs text-marca-300">Total a pagar</span>
                        <span class="font-display text-2xl font-extrabold tracking-tight">Q{{ number_format($pedido->total, 2) }}</span>
                    </div>
                </div>

                <div class="mb-5 flex flex-col gap-3 rounded-lg bg-slate-100/80 p-4 md:flex-row md:items-center">
                    <div class="flex w-fit items-center gap-2 rounded-lg bg-white px-3 py-1.5 shadow-sm"
                         x-data="{ s: {{ max(0, (int) now()->diffInSeconds($pedido->vence_at, false)) }}, d(n) { return String(n).padStart(2, '0') } }" x-init="setInterval(() => s = Math.max(0, s - 1), 1000)">
                        <x-icono nombre="candado" class="size-5 text-red-600" />
                        <span class="flex flex-col">
                            <span class="text-[10px] text-slate-500 uppercase">Tiempo restante</span>
                            <span class="font-display text-lg font-bold tracking-wider text-red-600" x-text="d(Math.floor(s / 3600)) + ':' + d(Math.floor((s % 3600) / 60)) + ':' + d(s % 60)"></span>
                        </span>
                    </div>
                    <p class="text-sm text-slate-600">Las unidades están apartadas hasta el <strong>{{ $pedido->vence_at->translatedFormat('j \d\e F \a \l\a\s H:i') }}</strong>. Si no recibimos el pago para entonces, el pedido se cancela y los productos vuelven a la venta.</p>
                </div>

                @if ($pedido->metodo_pago === MetodoPago::Paypal)
                    @if ($paypalClientId)
                        <div class="grid gap-5 rounded-lg bg-superficie p-4 md:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <h3 class="font-display font-bold text-slate-900">PayPal o tarjeta de débito / crédito</h3>
                                <p class="text-sm text-slate-600">Al pulsar un botón se abre la ventana segura de PayPal. Para pagar con tarjeta no necesitas cuenta de PayPal; los datos de tu tarjeta nunca pasan por ForjaPC.</p>
                                <ul class="flex flex-col gap-1 text-sm text-slate-600">
                                    <li class="flex items-center gap-2"><span class="text-marca-600">✓</span> Confirmación automática en segundos</li>
                                    <li class="flex items-center gap-2"><span class="text-marca-600">✓</span> El cobro se hace en dólares al tipo de cambio del día</li>
                                </ul>
                                <p class="mt-auto text-xs text-slate-500">Modo de pruebas (sandbox): usa una cuenta de comprador de PayPal Developer. No se cobra dinero real.</p>
                            </div>
                            <div class="relative isolate z-0 rounded-xl bg-white p-4 shadow-sm" x-data="pagoPaypal('{{ route('pedidos.paypal.orden', $pedido) }}', '{{ route('pedidos.paypal.capturar', $pedido) }}')">
                                <div id="paypal-botones"></div>
                                <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm text-red-600"></p>
                                <p x-show="procesando" x-cloak class="mt-2 text-sm text-slate-600">Confirmando el pago…</p>
                            </div>
                        </div>
                        <script src="https://www.paypal.com/sdk/js?client-id={{ $paypalClientId }}&currency=USD&intent=capture&locale=es_GT{{ config('services.paypal.modo') === 'live' ? '' : '&buyer-country=GT' }}"></script>
                        <script>
                            function pagoPaypal(urlOrden, urlCaptura) {
                                return {
                                    error: null,
                                    procesando: false,
                                    init() {
                                        const token = document.querySelector('meta[name=csrf-token]').content;
                                        const enviar = (url, cuerpo = {}) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }, body: JSON.stringify(cuerpo) })
                                            .then(async r => { const d = await r.json(); if (! r.ok) throw new Error(d.error || 'Error'); return d; });

                                        paypal.Buttons({
                                            createOrder: () => enviar(urlOrden).then(d => d.id).catch(e => { this.error = e.message; throw e; }),
                                            onApprove: (datos) => {
                                                this.procesando = true;
                                                const capturar = () => enviar(urlCaptura, { orderID: datos.orderID });
                                                // Si la conexión falla, se reintenta y luego se recarga: al abrir el pedido el servidor consulta PayPal y aplica el pago.
                                                return capturar()
                                                    .catch(e => e instanceof TypeError ? new Promise(r => setTimeout(r, 2000)).then(capturar) : Promise.reject(e))
                                                    .then(d => window.location = d.redireccion)
                                                    .catch(e => {
                                                        if (e instanceof TypeError) { this.error = 'Se interrumpió la conexión. Estamos confirmando tu pago con PayPal…'; setTimeout(() => window.location.reload(), 4000); return; }
                                                        this.procesando = false; this.error = e.message;
                                                    });
                                            },
                                            onError: () => { this.error = 'PayPal no pudo procesar el pago. Intenta de nuevo.'; },
                                        }).render('#paypal-botones');
                                    },
                                };
                            }
                        </script>
                    @else
                        <p class="mb-4 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-900">El pago con PayPal no está disponible en este momento. Puedes pagar por transferencia usando el formulario de abajo.</p>
                    @endif
                @endif

                @if ($pedido->metodo_pago === MetodoPago::Transferencia || ! $paypalClientId)
                    <div class="grid gap-5 lg:grid-cols-12">
                        <div class="flex flex-col gap-3 lg:col-span-5">
                            <h3 class="font-display font-bold text-slate-900">Datos para depositar</h3>
                            <p class="text-sm text-slate-600">Transfiere o deposita exactamente <strong class="text-slate-900">Q{{ number_format($pedido->total, 2) }}</strong> y escribe <strong class="text-slate-900">{{ $pedido->numero }}</strong> como referencia.</p>
                            <div class="flex flex-col gap-2 rounded-lg bg-slate-100/80 p-4 shadow-sm" x-data="{ copiado: false }">
                                <span class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">Cuenta de ForjaPC</span>
                                <div class="flex items-center justify-between gap-2 rounded bg-white px-3 py-2">
                                    <span class="font-bold tracking-wide text-slate-900 select-all" x-ref="cuenta">{{ $cuentaBancaria }}</span>
                                    <button type="button" @click="navigator.clipboard.writeText($refs.cuenta.innerText); copiado = true; setTimeout(() => copiado = false, 2000)" class="shrink-0 text-xs font-semibold text-marca-600" x-text="copiado ? 'Copiado' : 'Copiar'">Copiar</button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-5 shadow-sm ring-1 ring-slate-200/70 lg:col-span-7">
                            @if ($porVerificar)
                                <div class="flex items-start gap-3">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-sky-100 text-sky-700"><x-icono nombre="lista" class="size-5" /></span>
                                    <div>
                                        <h3 class="font-display font-bold text-slate-900">Estamos verificando tu comprobante</h3>
                                        <p class="text-sm text-slate-600">Recibimos tu comprobante ({{ $porVerificar->banco }}, boleta {{ $porVerificar->numero_boleta }}). Te avisaremos por correo cuando el pago quede confirmado. Mientras lo revisamos, tu pedido no se cancela.</p>
                                    </div>
                                </div>
                            @else
                                <h3 class="font-display font-bold text-slate-900">Registra tu comprobante</h3>
                                <p class="mb-4 text-sm text-slate-500">Lo revisamos en horario de oficina y te avisamos por correo.</p>
                                <form method="POST" action="{{ route('pedidos.transferencia', $pedido) }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                                    @csrf
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="flex flex-col gap-1">
                                            <label for="banco" class="text-sm font-semibold text-slate-800">Banco <span class="text-red-600">*</span></label>
                                            <select id="banco" name="banco" required class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-marca-600">
                                                @foreach (['Banco Industrial', 'Banrural', 'BAM', 'G&T Continental', 'Banco Promerica', 'BAC Credomatic', 'Otro'] as $banco)
                                                    <option @selected(old('banco') === $banco)>{{ $banco }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="flex flex-col gap-1">
                                            <label for="numero_boleta" class="text-sm font-semibold text-slate-800">No. de boleta o referencia <span class="text-red-600">*</span></label>
                                            <input id="numero_boleta" name="numero_boleta" value="{{ old('numero_boleta') }}" required placeholder="Ej. 94819204" class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-marca-600">
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label for="monto" class="text-sm font-semibold text-slate-800">Monto depositado (Q) <span class="text-red-600">*</span></label>
                                        <input id="monto" name="monto" type="number" step="0.01" value="{{ old('monto', $pedido->total) }}" required class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-marca-600">
                                    </div>
                                    <div class="flex flex-col gap-1" x-data="{ archivo: '' }">
                                        <span class="text-sm font-semibold text-slate-800">Foto o PDF del comprobante <span class="text-red-600">*</span></span>
                                        <label for="comprobante" class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg bg-white p-4 text-center ring-1 ring-slate-200 ring-dashed transition hover:bg-slate-100">
                                            <x-icono nombre="paquete" class="size-8 text-marca-600" />
                                            <span class="text-sm font-semibold text-slate-800" x-text="archivo || 'Haz clic para elegir la imagen o el PDF'">Haz clic para elegir la imagen o el PDF</span>
                                            <span class="text-xs text-slate-500">JPG, PNG o PDF de hasta 4 MB</span>
                                        </label>
                                        <input id="comprobante" type="file" name="comprobante" accept="image/*,application/pdf" required class="sr-only" @change="archivo = $event.target.files[0]?.name ?? ''">
                                    </div>
                                    <x-input-error :messages="$errors->all()" />
                                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-marca-600 py-3 font-display font-bold text-white shadow-sm transition hover:bg-marca-700">
                                        <x-icono nombre="lista" class="size-5" /> Enviar comprobante
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            </section>
        @endif

        {{-- Entrega --}}
        <section class="mb-5 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6">
            <h2 class="mb-4 flex items-center gap-2 font-display text-lg font-bold text-slate-900"><x-icono :nombre="$pedido->esEnvio() ? 'camion' : 'tienda'" class="size-5.5 text-marca-600" /> Entrega</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="flex flex-col gap-1 rounded-lg bg-slate-100/80 p-4">
                    <span class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">{{ $pedido->esEnvio() ? 'Dirección de entrega' : 'Recoger en tienda' }}</span>
                    @if ($pedido->esEnvio())
                        <p class="text-sm text-slate-800">{{ $pedido->direccion_envio['direccion'] }}, {{ $pedido->direccion_envio['municipio'] }}, {{ $pedido->direccion_envio['departamento'] }}</p>
                        <span class="mt-1 text-xs text-slate-500">Recibe: {{ $pedido->direccion_envio['nombre_receptor'] }} · Tel. {{ $pedido->direccion_envio['telefono'] }}</span>
                    @else
                        <p class="text-sm text-slate-800">Tienda ForjaPC, Ciudad de Guatemala.</p>
                        <span class="mt-1 text-xs text-slate-500">Lleva tu DPI y el número de pedido.</span>
                    @endif
                </div>
                <div class="flex flex-col gap-1 rounded-lg bg-slate-100/80 p-4">
                    <span class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">{{ $pedido->esEnvio() ? 'Mensajería' : 'Cuándo recoger' }}</span>
                    @if ($pedido->esEnvio())
                        <p class="text-sm font-semibold text-slate-800">{{ $pedido->mensajeria ?? 'Se asigna al despachar' }}</p>
                        <span class="text-xs text-slate-500">{{ $pedido->mensajeria ? 'Tu paquete ya va en camino.' : 'Te avisaremos por correo cuando salga.' }}</span>
                    @else
                        <p class="text-sm font-semibold text-slate-800">{{ $alcanzadas->has(EstadoPedido::ListoEntrega->value) ? '¡Ya está listo!' : 'Te avisamos por correo' }}</p>
                        <span class="text-xs text-slate-500">Cuando esté listo para entregarse.</span>
                    @endif
                </div>
                <div class="flex flex-col gap-1 rounded-lg bg-slate-100/80 p-4">
                    <span class="text-[11px] font-bold tracking-wide text-slate-500 uppercase">{{ $pedido->esEnvio() ? 'Número de guía' : 'Facturación' }}</span>
                    @if ($pedido->esEnvio())
                        <p class="font-display text-lg font-bold text-slate-900">{{ $pedido->numero_guia ?? '—' }}</p>
                        <span class="text-xs text-slate-500">{{ $pedido->numero_guia ? 'Rastréalo en el sitio de la mensajería.' : 'Lo verás aquí cuando despachemos tu pedido.' }}</span>
                    @else
                        <p class="text-sm font-semibold text-slate-800">{{ $pedido->nombre_facturacion }}</p>
                        <span class="text-xs text-slate-500">NIT {{ $pedido->nit }}</span>
                    @endif
                </div>
            </div>
        </section>

        <div class="grid items-start gap-5 lg:grid-cols-12">
            {{-- Artículos --}}
            <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70 sm:p-6 lg:col-span-8">
                <h2 class="mb-4 flex items-center gap-2 font-display text-lg font-bold text-slate-900"><x-icono nombre="paquete" class="size-5.5 text-marca-600" /> Artículos ({{ $piezas }})</h2>
                <div class="flex flex-col gap-3">
                    @foreach ($pedido->itemsAgrupados() as $grupo => $items)
                        @php
                            $primero = $items->first();
                        @endphp
                        @if ($primero->grupo_configuracion)
                            <div class="flex flex-col gap-3 rounded-lg bg-slate-100/80 p-4 md:flex-row">
                                <span class="flex size-20 shrink-0 items-center justify-center rounded-lg bg-marca-800 text-cian-400 shadow-sm"><x-icono nombre="monitor" class="size-10" stroke-width="1.25" /></span>
                                <div class="flex flex-1 flex-col gap-1">
                                    <span class="w-fit rounded bg-marca-100 px-2 py-0.5 text-[11px] font-bold text-marca-700 uppercase">PC a la medida</span>
                                    <h3 class="font-display font-bold text-slate-900">{{ $primero->nombre_configuracion }}</h3>
                                    <ul class="grid gap-x-4 gap-y-0.5 text-xs text-slate-600 sm:grid-cols-2">
                                        @foreach ($items as $item)
                                            <li class="flex justify-between gap-2"><span class="truncate">{{ $item->cantidad > 1 ? $item->cantidad.'× ' : '' }}{{ $item->descripcion }}</span><span class="shrink-0">Q{{ number_format($item->subtotal, 2) }}</span></li>
                                        @endforeach
                                    </ul>
                                    @if ($primero->con_armado)
                                        <span class="mt-1 flex items-center gap-1 text-xs font-semibold text-marca-600"><x-icono nombre="escudo" class="size-4" /> Incluye armado y pruebas en taller</span>
                                    @endif
                                </div>
                                <span class="shrink-0 text-right font-display text-lg font-bold text-slate-900">Q{{ number_format($items->sum('subtotal'), 2) }}</span>
                            </div>
                        @else
                            @foreach ($items as $item)
                                <div class="flex items-center gap-4 rounded-lg bg-slate-100/80 p-4">
                                    <span class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white shadow-sm">
                                        @if ($url = $item->producto?->imagenPrincipalUrl())
                                            <img src="{{ $url }}" alt="" class="h-full w-full object-contain p-1">
                                        @else
                                            <x-icono :nombre="$item->producto?->categoria?->icono ?? 'paquete'" class="size-8 text-slate-300" />
                                        @endif
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        @if ($item->producto)
                                            <a href="{{ route('tienda.producto', $item->producto->slug) }}" class="font-display font-bold text-slate-900 hover:text-marca-600">{{ $item->descripcion }}</a>
                                        @else
                                            <span class="font-display font-bold text-slate-900">{{ $item->descripcion }}</span>
                                        @endif
                                        <p class="text-xs text-slate-500">{{ $item->cantidad }} {{ $item->cantidad === 1 ? 'unidad' : 'unidades' }} · Q{{ number_format($item->precio_unitario, 2) }} c/u</p>
                                    </div>
                                    <span class="shrink-0 font-display text-lg font-bold text-slate-900">Q{{ number_format($item->subtotal, 2) }}</span>
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                    @if ($pedido->costo_armado > 0)
                        <div class="flex items-center gap-4 rounded-lg bg-slate-100/80 p-4">
                            <span class="flex size-16 shrink-0 items-center justify-center rounded-lg bg-white text-marca-600 shadow-sm"><x-icono nombre="herramienta" class="size-8" /></span>
                            <div class="min-w-0 flex-1">
                                <span class="font-display font-bold text-slate-900">Servicio de armado y pruebas</span>
                                <p class="text-xs text-slate-500">Cableado ordenado y pruebas de encendido, temperatura y estabilidad.</p>
                            </div>
                            <span class="shrink-0 font-display text-lg font-bold text-slate-900">Q{{ number_format($pedido->costo_armado, 2) }}</span>
                        </div>
                    @endif
                </div>
            </section>

            <aside class="flex flex-col gap-5 lg:col-span-4">
                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="mb-3 flex items-center gap-2 font-display text-lg font-bold text-slate-900"><x-icono nombre="lista" class="size-5 text-marca-600" /> Resumen</h2>
                    <dl class="flex flex-col gap-2 text-sm text-slate-600">
                        <div class="flex justify-between"><dt>Productos</dt><dd class="font-medium text-slate-900">Q{{ number_format($pedido->subtotal, 2) }}</dd></div>
                        @if ($pedido->costo_armado > 0)<div class="flex justify-between"><dt>Armado y pruebas</dt><dd class="font-medium text-slate-900">Q{{ number_format($pedido->costo_armado, 2) }}</dd></div>@endif
                        <div class="flex justify-between"><dt>Envío</dt><dd class="font-medium text-slate-900">{{ $pedido->costo_envio > 0 ? 'Q'.number_format($pedido->costo_envio, 2) : 'Sin costo' }}</dd></div>
                        <div class="flex flex-col gap-1 rounded bg-slate-100 p-2.5 text-xs">
                            <div class="flex justify-between"><dt>Base imponible</dt><dd>Q{{ number_format($pedido->base_imponible, 2) }}</dd></div>
                            <div class="flex justify-between"><dt>IVA (12 %)</dt><dd>Q{{ number_format($pedido->iva, 2) }}</dd></div>
                        </div>
                    </dl>
                    <div class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                        <span class="font-display font-bold text-slate-900">Total</span>
                        <span class="font-display text-2xl font-extrabold text-slate-900">Q{{ number_format($pedido->total, 2) }}</span>
                    </div>
                    <div class="mt-3 flex flex-col gap-0.5 rounded-lg bg-slate-100/80 p-3 text-xs">
                        <span class="text-[11px] font-bold tracking-wide text-marca-600 uppercase">Facturación</span>
                        <span class="font-medium text-slate-900">{{ $pedido->nombre_facturacion }}</span>
                        <span class="text-slate-500">NIT {{ $pedido->nit }} · Pago: {{ $pedido->metodo_pago->etiqueta() }}</span>
                    </div>
                </section>

                <section class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200/70">
                    <h2 class="mb-3 flex items-center gap-2 font-display text-lg font-bold text-slate-900"><x-icono nombre="cambio" class="size-5 text-marca-600" /> Historial</h2>
                    <ol class="relative flex flex-col gap-4 pl-6 before:absolute before:top-2 before:bottom-2 before:left-2 before:w-0.5 before:bg-slate-200">
                        @foreach ($pedido->historial as $registro)
                            <li class="relative">
                                <span @class(['absolute top-1 -left-[23px] size-3.5 rounded-full', 'bg-marca-600' => $loop->last, 'bg-slate-300' => ! $loop->last])></span>
                                <span class="block text-[11px] text-slate-500">{{ $registro->created_at->translatedFormat('j M Y · H:i') }}</span>
                                <span class="block text-sm font-semibold text-slate-900">{{ $registro->estado_nuevo->etiqueta() }}</span>
                                @if ($registro->comentario)<span class="block text-xs text-slate-500">{{ $registro->comentario }}</span>@endif
                            </li>
                        @endforeach
                    </ol>
                </section>
            </aside>
        </div>

        <div class="mt-5 flex flex-col items-center justify-between gap-3 rounded-lg bg-slate-200/60 p-4 text-sm text-slate-600 sm:flex-row">
            <span class="flex items-center gap-2"><x-icono nombre="chat" class="size-5 text-slate-500" /> ¿Tienes dudas sobre tu pedido? Escríbenos a {{ \App\Models\ParametroSistema::valor('correo_contacto') }} o por WhatsApp.</span>
            <a href="https://wa.me/{{ \App\Models\ParametroSistema::valor('whatsapp_contacto') }}" target="_blank" rel="noopener" class="shrink-0 rounded bg-white px-4 py-1.5 font-semibold text-slate-900 shadow-sm hover:bg-slate-50">Escribir por WhatsApp</a>
        </div>

        {{-- Confirmación de cancelación --}}
        @if ($pedido->estaPendienteDePago())
            <div x-show="cancelar" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-marca-900/60 p-4 backdrop-blur-sm" @keydown.escape.window="cancelar = false" role="dialog" aria-modal="true" aria-labelledby="titulo-cancelar">
                <div class="flex w-full max-w-md flex-col gap-4 rounded-xl bg-white p-6 shadow-xl" @click.outside="cancelar = false">
                    <span class="flex size-12 items-center justify-center rounded-full bg-red-50 text-xl font-bold text-red-600">!</span>
                    <div>
                        <h2 id="titulo-cancelar" class="font-display text-lg font-bold text-slate-900">¿Cancelar el pedido {{ $pedido->numero }}?</h2>
                        <p class="mt-1 text-sm text-slate-600">Los productos que tenías apartados vuelven a la venta y el pedido queda anulado. No se realizó ningún cobro.</p>
                    </div>
                    <form method="POST" action="{{ route('pedidos.cancelar', $pedido) }}" class="flex flex-col-reverse justify-end gap-2 sm:flex-row">
                        @csrf
                        <button type="button" @click="cancelar = false" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-200">No, mantener mi pedido</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">Sí, cancelar</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
