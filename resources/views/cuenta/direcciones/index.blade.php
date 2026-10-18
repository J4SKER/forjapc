<x-app-layout title="Mis direcciones">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-slate-900">Mis direcciones</h1>
            @if ($direcciones->count() < $maximo)
                <a href="{{ route('direcciones.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-acento-600 px-4 py-2 text-sm font-semibold text-white hover:bg-acento-700">
                    <x-icono nombre="ubicacion" class="size-4" /> Nueva dirección
                </a>
            @endif
        </div>
    </x-slot>

    <div class="mx-auto grid max-w-7xl items-start gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[17rem_1fr] lg:px-8">
        @include('cuenta.partials.menu')

        <div>
            @if ($direcciones->isEmpty())
                <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-slate-200">
                    <x-icono nombre="ubicacion" class="mx-auto size-10 text-slate-300" />
                    <p class="mt-3 text-slate-600">Todavía no tienes direcciones de entrega.</p>
                    <a href="{{ route('direcciones.create') }}" class="mt-4 inline-block font-semibold text-acento-600 hover:underline">Agregar mi primera dirección</a>
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($direcciones as $direccion)
                        <article @class([
                            'rounded-xl bg-white p-5 shadow-sm ring-1',
                            'ring-2 ring-marca-500' => $direccion->es_principal,
                            'ring-slate-200' => ! $direccion->es_principal,
                        ])>
                            <div class="flex items-center justify-between gap-2">
                                <h2 class="font-semibold text-slate-900">{{ $direccion->alias }}</h2>
                                @if ($direccion->es_principal)
                                    <span class="rounded-full bg-marca-50 px-2 py-0.5 text-xs font-semibold text-marca-700">Principal</span>
                                @endif
                            </div>
                            <address class="mt-2 text-sm leading-relaxed not-italic text-slate-600">
                                {{ $direccion->nombre_receptor }} · Tel. {{ $direccion->telefono }}<br>
                                {{ $direccion->direccion }}<br>
                                {{ $direccion->municipio }}, {{ $direccion->departamento->nombre }}
                                @if ($direccion->referencias)
                                    <br><span class="text-slate-400">Ref.: {{ $direccion->referencias }}</span>
                                @endif
                            </address>
                            <p class="mt-2 text-xs text-slate-500">Envío: Q{{ number_format($direccion->departamento->tarifa_envio, 2) }} · {{ $direccion->departamento->dias_entrega }} día(s) hábil(es)</p>
                            <div class="mt-4 flex gap-4 text-sm">
                                <a href="{{ route('direcciones.edit', $direccion) }}" class="font-medium text-marca-600 hover:underline">Editar</a>
                                <form method="POST" action="{{ route('direcciones.destroy', $direccion) }}" onsubmit="return confirm('¿Eliminar esta dirección?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-acento-600 hover:underline">Eliminar</button>
                                </form>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
