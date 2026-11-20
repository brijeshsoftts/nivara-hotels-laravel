<div
    x-show="open"
    x-cloak
    @click.outside="open = false"
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    data-slot="select-content"
    {{ $attributes->class([
        "absolute z-50 mt-1 max-h-60 w-full min-w-32 overflow-auto rounded-lg border bg-popover p-1 text-popover-foreground shadow-md outline-none"
    ]) }}
>
    {{ $slot }}
</div>
