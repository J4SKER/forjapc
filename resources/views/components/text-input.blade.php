@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-slate-300 focus:border-marca-500 focus:ring-marca-500 rounded-lg shadow-sm text-sm']) }}>
