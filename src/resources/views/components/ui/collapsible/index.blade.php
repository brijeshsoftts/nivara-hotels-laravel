@props([
    "open" => false,
])

<div
    data-slot="collapsible"
    x-data="{ open: @js($open) }"
    {{ $attributes }}
>
    {{ $slot }}
</div>
