@props([
    "placeholder" => "Select...",
])

<span
    data-slot="select-value"
    x-text="label || '{{ $placeholder }}'"
    {{ $attributes->class(["truncate text-foreground"]) }}
>
    {{ $slot }}
</span>
