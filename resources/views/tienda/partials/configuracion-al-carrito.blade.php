{{-- Se reemplaza por "Agregar al carrito" en el sprint de carrito y checkout. --}}
<button type="button" disabled class="w-full cursor-not-allowed rounded-lg bg-acento-600/60 px-4 py-2.5 text-sm font-semibold text-white">
    {{ $puedeComprarse ? 'Agregar al carrito (muy pronto)' : 'Completa una configuración compatible' }}
</button>
