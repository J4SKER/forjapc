@props(['nombre', 'etiqueta', 'icono' => null, 'tipo' => 'text', 'valor' => null, 'ayuda' => null])

{{-- Campo de formulario con ícono, ayuda, error y (en contraseñas) botón para mostrar el texto. --}}
<div class="flex flex-col gap-1" @if ($tipo === 'password') x-data="{ visible: false }" @endif>
    <div class="flex items-center justify-between gap-2">
        <label for="{{ $nombre }}" class="text-sm font-semibold text-slate-800">{{ $etiqueta }}</label>
        {{ $accion ?? '' }}
    </div>
    <div @class([
        'relative flex items-center rounded-lg bg-slate-100 shadow-sm transition focus-within:bg-white focus-within:shadow-md focus-within:ring-2 focus-within:ring-marca-600',
        'ring-2 ring-red-500' => $errors->has($nombre),
    ])>
        @if ($icono)
            <span class="pointer-events-none flex items-center pr-2 pl-3.5 text-slate-500"><x-icono :nombre="$icono" class="size-5" /></span>
        @endif
        <input id="{{ $nombre }}" name="{{ $nombre }}" value="{{ $tipo === 'password' ? '' : old($nombre, $valor) }}"
               @if ($tipo === 'password') :type="visible ? 'text' : 'password'" type="password" @else type="{{ $tipo }}" @endif
               {{ $attributes->merge(['class' => 'w-full border-0 bg-transparent py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0 '.($icono ? 'pl-0' : 'pl-3.5').' '.($tipo === 'password' ? 'pr-11' : 'pr-4')]) }}>
        @if ($tipo === 'password')
            <button type="button" @click="visible = ! visible" class="absolute right-2.5 rounded p-1 text-xs font-semibold text-slate-500 hover:text-slate-900" :aria-label="visible ? 'Ocultar contraseña' : 'Mostrar contraseña'" x-text="visible ? 'Ocultar' : 'Ver'">Ver</button>
        @endif
    </div>
    @if ($ayuda && ! $errors->has($nombre))
        <p class="text-xs text-slate-500">{{ $ayuda }}</p>
    @endif
    <x-input-error :messages="$errors->get($nombre)" />
</div>
