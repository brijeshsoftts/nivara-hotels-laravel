<div
    data-slot="popover"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    {{ $attributes->class(["relative inline-block"]) }}
>
    {{ $slot }}
</div>
