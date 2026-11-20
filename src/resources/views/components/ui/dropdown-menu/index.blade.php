<div
    data-slot="dropdown-menu"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    {{ $attributes->class(["relative inline-block"]) }}
>
    {{ $slot }}
</div>
