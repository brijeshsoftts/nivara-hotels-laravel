@props([
    "inset" => false,
])

<div
    data-slot="dropdown-menu-label"
    data-inset="{{ $inset ? 'true' : 'false' }}"
    {{ $attributes->class([
        "px-1.5 py-1 text-xs font-medium text-muted-foreground",
        "pl-7" => $inset,
    ]) }}
>
    {{ $slot }}
</div>
