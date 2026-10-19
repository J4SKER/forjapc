// Alpine.js lo carga Livewire automáticamente (versión CSP); no se importa aquí para evitar dos instancias.
//
// La política de contenido no permite JavaScript dentro del HTML (<script> sin nonce, onclick="",
// eval). Por eso toda la lógica vive aquí: las vistas solo usan expresiones sencillas de Alpine
// (asignaciones, comparaciones, llamadas a métodos) sobre estos componentes. Los datos iniciales
// que vienen del servidor se pasan como JSON en data-inicial y se leen con x-data="nombre($el)".

const inicial = (el) => JSON.parse(el?.dataset.inicial || '{}');

const quetzales = (v) => 'Q' + (Number(v) || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const numero = (v) => Number(v) || 0;

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Copia un texto (o el contenido de un elemento) y muestra «Copiado» unos segundos.
    Alpine.data('copiar', () => ({
        copiado: false,
        copiar(fuente) {
            const texto = typeof fuente === 'string' ? fuente : (fuente.value ?? fuente.innerText);
            navigator.clipboard.writeText(texto.trim());
            this.copiado = true;
            setTimeout(() => (this.copiado = false), 2000);
        },
    }));

    // Mensaje que desaparece solo («Guardado.»).
    Alpine.data('avisoTemporal', () => ({
        show: true,
        init() {
            setTimeout(() => (this.show = false), 2000);
        },
    }));

    // Selector de cantidad de la ficha de producto (componente Livewire).
    Alpine.data('selectorCantidad', (maximo) => ({
        maximo,
        menos() {
            this.$wire.cantidad = Math.max(1, this.$wire.cantidad - 1);
        },
        mas() {
            this.$wire.cantidad = Math.min(this.maximo, this.$wire.cantidad + 1);
        },
    }));

    // Cuenta regresiva del plazo de pago de un pedido.
    Alpine.data('cuentaRegresiva', (segundos) => ({
        s: segundos,
        init() {
            this.reloj = setInterval(() => (this.s = Math.max(0, this.s - 1)), 1000);
        },
        destroy() {
            clearInterval(this.reloj);
        },
        get texto() {
            const d = (n) => String(n).padStart(2, '0');
            return d(Math.floor(this.s / 3600)) + ':' + d(Math.floor((this.s % 3600) / 60)) + ':' + d(this.s % 60);
        },
    }));

    // Nombre del archivo elegido en un campo de archivo.
    Alpine.data('archivoElegido', () => ({
        archivo: '',
        elegir(evento) {
            this.archivo = evento.target.files[0]?.name ?? '';
        },
    }));

    // Botones de PayPal del pedido. Si la conexión falla al capturar, se reintenta y luego se
    // recarga: al abrir el pedido el servidor consulta PayPal y aplica el pago.
    Alpine.data('pagoPaypal', (urlOrden, urlCaptura) => ({
        error: null,
        procesando: false,
        init() {
            const token = document.querySelector('meta[name=csrf-token]').content;
            const enviar = (url, cuerpo = {}) => fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, Accept: 'application/json' }, body: JSON.stringify(cuerpo) })
                .then(async (r) => { const d = await r.json(); if (! r.ok) throw new Error(d.error || 'Error'); return d; });

            if (! window.paypal) {
                this.error = 'No se pudo cargar PayPal. Revisa tu conexión y recarga la página.';
                return;
            }

            window.paypal.Buttons({
                createOrder: () => enviar(urlOrden).then((d) => d.id).catch((e) => { this.error = e.message; throw e; }),
                onApprove: (datos) => {
                    this.procesando = true;
                    const capturar = () => enviar(urlCaptura, { orderID: datos.orderID });

                    return capturar()
                        .catch((e) => (e instanceof TypeError ? new Promise((r) => setTimeout(r, 2000)).then(capturar) : Promise.reject(e)))
                        .then((d) => (window.location = d.redireccion))
                        .catch((e) => {
                            if (e instanceof TypeError) {
                                this.error = 'Se interrumpió la conexión. Estamos confirmando tu pago con PayPal…';
                                setTimeout(() => window.location.reload(), 4000);
                                return;
                            }
                            this.procesando = false;
                            this.error = e.message;
                        });
                },
                onError: () => { this.error = 'PayPal no pudo procesar el pago. Intenta de nuevo.'; },
            }).render(this.$refs.botones);
        },
    }));

    // Checkout: costo de envío según la dirección y NIT o consumidor final.
    Alpine.data('checkout', (el) => ({
        entrega: 'RECOGER',
        direccion: '',
        pago: 'TRANSFERENCIA',
        nit: 'CF',
        tarifas: {},
        base: 0,
        ...inicial(el),
        get envio() {
            return this.entrega === 'ENVIO' ? (this.tarifas[this.direccion] ?? 0) : 0;
        },
        get esCf() {
            return this.nit.trim().toUpperCase() === 'CF';
        },
        alternarCf(evento) {
            this.nit = evento.target.checked ? 'CF' : '';
            this.$nextTick(() => this.esCf || this.$refs.nit.focus());
        },
        q: quetzales,
    }));

    // Formulario de producto: muestra las especificaciones del tipo de componente de la categoría.
    Alpine.data('formProducto', (el) => ({
        categoria: '',
        tipos: {},
        ...inicial(el),
        get tipo() {
            return this.tipos[this.categoria] ?? null;
        },
    }));

    // Formulario de compra a proveedor: líneas dinámicas, costo sugerido y totales con IVA.
    Alpine.data('formCompra', (el) => ({
        lineas: [],
        productos: [],
        iva: 0,
        ...inicial(el),
        init() {
            if (! this.lineas.length) this.agregar();
        },
        agregar() {
            this.lineas.push({ producto_id: '', cantidad: 1, costo_unitario: '' });
        },
        quitar(i) {
            this.lineas.splice(i, 1);
            if (! this.lineas.length) this.agregar();
        },
        sugerirCosto(linea) {
            const p = this.productos.find((p) => p.id == linea.producto_id);
            if (p && ! linea.costo_unitario && p.costo > 0) linea.costo_unitario = p.costo.toFixed(2);
        },
        importe(linea) {
            return numero(linea.cantidad) * numero(linea.costo_unitario);
        },
        get subtotal() {
            return this.lineas.reduce((s, l) => s + this.importe(l), 0);
        },
        q: quetzales,
    }));

    // Partida contable manual: una línea es debe o haber, y la partida debe cuadrar.
    Alpine.data('formPartida', (el) => ({
        lineas: [],
        ...inicial(el),
        agregar() {
            this.lineas.push({ codigo: '', debe: '', haber: '' });
        },
        soloDebe(linea) {
            if (linea.debe) linea.haber = '';
        },
        soloHaber(linea) {
            if (linea.haber) linea.debe = '';
        },
        get debe() {
            return this.lineas.reduce((s, l) => s + numero(l.debe), 0);
        },
        get haber() {
            return this.lineas.reduce((s, l) => s + numero(l.haber), 0);
        },
        get cuadra() {
            return this.debe > 0 && Math.abs(this.debe - this.haber) < 0.005;
        },
        q: quetzales,
    }));

    // Ventana modal (Breeze) con el foco atrapado dentro mientras está abierta.
    Alpine.data('modal', (el) => ({
        show: false,
        nombre: '',
        enfocar: false,
        ...inicial(el),
        init() {
            this.$watch('show', (valor) => {
                document.body.classList.toggle('overflow-y-hidden', valor);
                if (valor && this.enfocar) setTimeout(() => this.firstFocusable()?.focus(), 100);
            });
        },
        abrir(evento) {
            if (evento.detail == this.nombre) this.show = true;
        },
        cerrar(evento) {
            if (evento.detail == this.nombre) this.show = false;
        },
        focusables() {
            const selector = 'a, button, input:not([type=\'hidden\']), textarea, select, details, [tabindex]:not([tabindex=\'-1\'])';
            return [...this.$el.querySelectorAll(selector)].filter((e) => ! e.hasAttribute('disabled'));
        },
        firstFocusable() { return this.focusables()[0]; },
        lastFocusable() { return this.focusables().slice(-1)[0]; },
        nextFocusable() { return this.focusables()[this.nextFocusableIndex()] || this.firstFocusable(); },
        prevFocusable() { return this.focusables()[this.prevFocusableIndex()] || this.lastFocusable(); },
        nextFocusableIndex() { return (this.focusables().indexOf(document.activeElement) + 1) % (this.focusables().length + 1); },
        prevFocusableIndex() { return Math.max(0, this.focusables().indexOf(document.activeElement)) - 1; },
        tab(evento) {
            if (! evento.shiftKey) this.nextFocusable()?.focus();
        },
    }));
});

// Medidas calculadas en el servidor (barras de avance, sangrías) sin usar style="", que la CSP
// bloquea: data-ancho="45%", data-alto="30%" o data-sangria="1.5rem". Se aplican al cargar la
// página y cada vez que Livewire actualiza un componente.
const MEDIDAS = { ancho: 'width', alto: 'height', sangria: 'padding-left' };

function aplicarMedidas(raiz = document) {
    raiz.querySelectorAll('[data-ancho], [data-alto], [data-sangria]').forEach((el) => {
        for (const [dato, propiedad] of Object.entries(MEDIDAS)) {
            if (el.dataset[dato] !== undefined) el.style.setProperty(propiedad, el.dataset[dato]);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => aplicarMedidas());
document.addEventListener('livewire:init', () => window.Livewire.hook('morphed', ({ el }) => aplicarMedidas(el)));

// <form data-confirmar="¿Seguro?"> pide confirmación antes de enviarse (reemplaza onsubmit="return confirm()").
document.addEventListener('submit', (evento) => {
    const mensaje = evento.target.closest('form[data-confirmar]')?.dataset.confirmar;
    if (mensaje && ! window.confirm(mensaje)) {
        evento.preventDefault();
        evento.stopImmediatePropagation();
    }
}, true);
