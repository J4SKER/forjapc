@props(['nombre'])

{{-- Iconos de línea propios de ForjaPC (24x24, trazo actual). --}}
<svg {{ $attributes->merge(['class' => 'size-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($nombre)
        @case('cpu')
            <rect x="6" y="6" width="12" height="12" rx="1.5" /><rect x="9.5" y="9.5" width="5" height="5" rx=".5" />
            <path d="M9 3v3M12 3v3M15 3v3M9 18v3M12 18v3M15 18v3M3 9h3M3 12h3M3 15h3M18 9h3M18 12h3M18 15h3" />
            @break
        @case('placa')
            <rect x="3" y="3" width="18" height="18" rx="2" /><rect x="6" y="6" width="6" height="6" rx="1" />
            <path d="M15 6v6M17.5 6v6M6 15.5h12M6 18h7" />
            @break
        @case('ram')
            <rect x="2" y="7" width="20" height="9" rx="1" /><path d="M6 10v3M10 10v3M14 10v3M18 10v3M5 16v2M19 16v2M11 16v2" />
            @break
        @case('gpu')
            <rect x="2" y="6" width="20" height="11" rx="1.5" /><circle cx="9" cy="11.5" r="3" /><circle cx="17" cy="11.5" r="1.5" /><path d="M5 17v2h8v-2" />
            @break
        @case('disco')
            <rect x="4" y="3" width="16" height="18" rx="2" /><circle cx="12" cy="10" r="4" /><circle cx="12" cy="10" r=".75" /><path d="M8 17.5h8" />
            @break
        @case('fuente')
            <rect x="3" y="5" width="18" height="14" rx="1.5" /><circle cx="9" cy="12" r="4" /><path d="M9 8v8M5 12h8M16 9h2M16 12h2M16 15h2" />
            @break
        @case('gabinete')
            <rect x="6" y="2" width="12" height="20" rx="1.5" /><circle cx="12" cy="15" r="3" /><path d="M9 5.5h6M9 8h6" />
            @break
        @case('ventilador')
            <circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="1.5" />
            <path d="M12 10.5c0-3 1-5.5 3.5-5.5M13.5 12c3 0 5.5 1 5.5 3.5M12 13.5c0 3-1 5.5-3.5 5.5M10.5 12c-3 0-5.5-1-5.5-3.5" />
            @break
        @case('monitor')
            <rect x="2" y="4" width="20" height="13" rx="1.5" /><path d="M8 21h8M12 17v4" />
            @break
        @case('teclado')
            <rect x="2" y="6" width="20" height="12" rx="1.5" /><path d="M6 10h.01M10 10h.01M14 10h.01M18 10h.01M6 14h.01M18 14h.01M9 14h6" />
            @break
        @case('mouse')
            <rect x="6" y="3" width="12" height="18" rx="6" /><path d="M12 7v3" />
            @break
        @case('audifonos')
            <path d="M3 18v-6a9 9 0 0 1 18 0v6" /><rect x="3" y="14" width="4" height="7" rx="1.5" /><rect x="17" y="14" width="4" height="7" rx="1.5" />
            @break
        @case('laptop')
            <rect x="4" y="4" width="16" height="11" rx="1.5" /><path d="M2 19h20l-2-4H4z" />
            @break
        @case('carrito')
            <circle cx="9" cy="20" r="1.25" /><circle cx="18" cy="20" r="1.25" /><path d="M2 3h3l2.7 12.2a1 1 0 0 0 1 .8h9.6a1 1 0 0 0 1-.8L21 7H6" />
            @break
        @case('usuario')
            <circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" />
            @break
        @case('buscar')
            <circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" />
            @break
        @case('menu')
            <path d="M4 6h16M4 12h16M4 18h16" />
            @break
        @case('cerrar')
            <path d="M6 6l12 12M18 6 6 18" />
            @break
        @case('chevron-abajo')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('chevron-derecha')
            <path d="m9 6 6 6-6 6" />
            @break
        @case('chevron-izquierda')
            <path d="m15 6-6 6 6 6" />
            @break
        @case('camion')
            <path d="M3 6h11v10H3zM14 9h4l3 3v4h-7" /><circle cx="7" cy="17.5" r="1.75" /><circle cx="17.5" cy="17.5" r="1.75" />
            @break
        @case('escudo')
            <path d="M12 3 4 6v6c0 4.5 3.4 8.2 8 9 4.6-.8 8-4.5 8-9V6z" /><path d="m9 12 2 2 4-4" />
            @break
        @case('tarjeta')
            <rect x="2" y="5" width="20" height="14" rx="2" /><path d="M2 10h20M6 15h4" />
            @break
        @case('herramienta')
            <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.5-.5-.5-2.5z" />
            @break
        @case('telefono')
            <path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2" />
            @break
        @case('correo')
            <rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />
            @break
        @case('ubicacion')
            <path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21" /><circle cx="12" cy="9.5" r="2.5" />
            @break
        @case('chat')
            <path d="M21 12a8 8 0 0 1-11.8 7L4 20l1.1-4.6A8 8 0 1 1 21 12" />
            @break
        @case('paquete')
            <path d="M21 8 12 3 3 8v8l9 5 9-5z" /><path d="m3 8 9 5 9-5M12 13v8" />
            @break
        @case('panel')
            <rect x="3" y="3" width="7" height="9" rx="1" /><rect x="14" y="3" width="7" height="5" rx="1" /><rect x="14" y="12" width="7" height="9" rx="1" /><rect x="3" y="16" width="7" height="5" rx="1" />
            @break
        @case('usuarios')
            <circle cx="9" cy="8" r="3.5" /><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 4 6" />
            @break
        @case('ajustes')
            <circle cx="12" cy="12" r="3" /><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9 7 7M17 17l2.1 2.1M4.9 19.1 7 17M17 7l2.1-2.1" />
            @break
        @case('lista')
            <path d="M9 6h12M9 12h12M9 18h12M4 6h.01M4 12h.01M4 18h.01" />
            @break
        @case('salir')
            <path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 17l5-5-5-5M15 12H3" />
            @break
        @case('tienda')
            <path d="M3 9 4.5 4h15L21 9M3 9v11h18V9M3 9h18M9 20v-6h6v6" />
            @break
        @case('candado')
            <rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" />
            @break
        @case('cambio')
            <path d="M4 7h13l-3-3M20 17H7l3 3" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
