<div
    data-slot="tooltip"
    x-data="{ open: false }"
    {{ $attributes->class(["relative inline-block"]) }}
>
    {{ $slot }}
</div>
