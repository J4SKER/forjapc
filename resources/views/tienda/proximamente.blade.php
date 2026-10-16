<x-app-layout :title="$titulo">
    <section class="mx-auto max-w-3xl px-4 py-16 text-center sm:px-6">
        <span class="inline-flex rounded-full bg-marca-50 p-5 text-marca-600">
            <x-icono :nombre="$icono" class="size-12" />
        </span>
        <h1 class="mt-6 text-3xl font-extrabold text-slate-900">{{ $titulo }}</h1>
        <p class="mt-3 text-slate-600">{{ $mensaje }}</p>
        <a href="{{ route('inicio') }}" class="mt-8 inline-flex items-center gap-2 rounded-lg bg-marca-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-marca-800">
            <x-icono nombre="tienda" class="size-4" /> Volver al inicio
        </a>
    </section>
</x-app-layout>
