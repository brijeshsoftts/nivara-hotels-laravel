<div
    x-show="open"
    x-cloak
    @click.outside="open = false"
    data-slot="combobox-content"
    {{ $attributes->class([
        "absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-lg border bg-popover p-1 text-popover-foreground shadow-md outline-none"
    ]) }}
>
    {{ $slot }}
</div>
