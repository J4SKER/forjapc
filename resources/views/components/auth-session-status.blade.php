@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-emerald-700 bg-emerald-50 rounded-lg px-4 py-3']) }}>
        {{ $status }}
    </div>
@endif
