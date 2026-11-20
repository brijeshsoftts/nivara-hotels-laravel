<div
    data-slot="command"
    x-data="{ search: '' }"
    {{ $attributes->class([
        "flex h-full w-full flex-col overflow-hidden rounded-md border bg-popover text-popover-foreground"
    ]) }}
>
    {{ $slot }}
</div>
