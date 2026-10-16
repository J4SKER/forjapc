<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $titulo }} · ForjaPC</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="flex min-h-screen flex-col items-center justify-center bg-slate-100 px-4 font-sans text-slate-800 antialiased">
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <x-application-logo class="size-10" />
            <span class="text-2xl font-extrabold text-marca-800">Forja<span class="text-acento-600">PC</span></span>
        </a>
        <main class="mt-8 w-full max-w-lg rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-slate-200">
            <p class="text-6xl font-extrabold text-marca-200">{{ $codigo }}</p>
            <h1 class="mt-2 text-2xl font-extrabold text-slate-900">{{ $titulo }}</h1>
            <p class="mt-2 text-slate-600">{{ $mensaje }}</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ url('/') }}" class="rounded-lg bg-acento-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-acento-700">Ir a la tienda</a>
                <button type="button" onclick="history.back()" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Regresar</button>
            </div>
        </main>
    </body>
</html>
