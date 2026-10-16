@if (\App\Http\Controllers\Tienda\ResenaController::comproElProducto(Auth::user(), $producto))
    <form method="POST" action="{{ route('tienda.resenas.store', $producto->slug) }}" class="mt-5 space-y-3 border-t border-slate-100 pt-4" x-data="{ estrellas: {{ (int) old('calificacion', 5) }} }">
        @csrf
        <h3 class="text-sm font-semibold text-slate-900">Escribe tu opinión</h3>
        <fieldset class="flex gap-1 text-2xl text-amber-500">
            <legend class="sr-only">Calificación</legend>
            @for ($i = 1; $i <= 5; $i++)
                <label class="cursor-pointer">
                    <input type="radio" name="calificacion" value="{{ $i }}" x-model.number="estrellas" class="sr-only">
                    <span x-text="estrellas >= {{ $i }} ? '★' : '☆'" aria-hidden="true"></span>
                    <span class="sr-only">{{ $i }} de 5</span>
                </label>
            @endfor
        </fieldset>
        <x-text-input name="titulo" :value="old('titulo')" placeholder="Título" class="block w-full" maxlength="120" required aria-label="Título" />
        <textarea name="comentario" rows="3" required minlength="10" maxlength="2000" placeholder="¿Qué te pareció?" aria-label="Comentario" class="block w-full rounded-lg border-slate-300 text-sm">{{ old('comentario') }}</textarea>
        <x-input-error :messages="$errors->all()" />
        <x-primary-button>Enviar opinión</x-primary-button>
    </form>
@else
    <p class="mt-5 rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-500">Solo los clientes que recibieron este producto pueden dejar su opinión.</p>
@endif
