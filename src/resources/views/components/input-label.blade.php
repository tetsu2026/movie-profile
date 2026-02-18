@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium mb-1.5']) }} style="color: #1D1D1F;">
    {{ $value ?? $slot }}
</label>
