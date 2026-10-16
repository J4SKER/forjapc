@php($sufijo = $categoria->exists ? $categoria->id : 'nueva')
<div>
    <x-input-label :for="'nombre-'.$sufijo" value="Nombre" />
    <x-text-input :id="'nombre-'.$sufijo" name="nombre" class="mt-1 block w-full" :value="old('nombre', $categoria->nombre)" required />
</div>
<div>
    <x-input-label :for="'slug-'.$sufijo" value="Dirección web" />
    <x-text-input :id="'slug-'.$sufijo" name="slug" class="mt-1 block w-full" :value="old('slug', $categoria->slug)" placeholder="se genera del nombre" />
</div>
<div>
    <x-input-label :for="'icono-'.$sufijo" value="Icono" />
    <select id="icono-{{ $sufijo }}" name="icono" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
        @foreach ($iconos as $icono)
            <option value="{{ $icono }}" @selected($categoria->icono === $icono)>{{ $icono }}</option>
        @endforeach
    </select>
</div>
<div>
    <x-input-label :for="'tipo-'.$sufijo" value="Tipo de componente" />
    <select id="tipo-{{ $sufijo }}" name="tipo_componente" class="mt-1 block w-full rounded-lg border-slate-300 text-sm">
        <option value="">Ninguno (periféricos)</option>
        @foreach ($tipos as $tipo)
            <option value="{{ $tipo->value }}" @selected($categoria->tipo_componente === $tipo)>{{ $tipo->etiqueta() }}</option>
        @endforeach
    </select>
</div>
<div>
    <x-input-label :for="'orden-'.$sufijo" value="Orden" />
    <x-text-input :id="'orden-'.$sufijo" name="orden" type="number" min="0" class="mt-1 block w-full" :value="old('orden', $categoria->orden)" required />
</div>
<label class="flex items-center gap-2 self-end pb-2 text-sm">
    <input type="hidden" name="activo" value="0">
    <input type="checkbox" name="activo" value="1" @checked($categoria->activo) class="rounded border-slate-300 text-marca-600"> Visible en la tienda
</label>
<x-input-error :messages="$errors->all()" class="sm:col-span-2" />
