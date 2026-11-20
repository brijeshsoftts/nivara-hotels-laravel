@props([
    "value",
])

<div
    data-slot="accordion-item"
    :data-state="isOpen('{{ $value }}') ? 'open' : 'closed'"
    {{ $attributes->class(["not-last:border-b"]) }}
>
    {{ $slot }}
</div>
