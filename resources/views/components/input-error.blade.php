@props(['messages'])

@php
    // Los errores de campos con comodín (p. ej. lineas.*) llegan anidados por cada fila.
    $messages = collect((array) $messages)->flatten()->unique()->values();
@endphp

@if ($messages->isNotEmpty())
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        @foreach ($messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
