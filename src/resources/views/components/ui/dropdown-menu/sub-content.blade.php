<div
    x-show="subOpen"
    x-cloak
    @mouseleave="subOpen = false"
    @click.outside="subOpen = false"
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    data-slot="dropdown-menu-sub-content"
    {{ $attributes->class([
        "absolute left-full top-0 z-50 ml-1 min-w-32 rounded-lg bg-popover p-1 text-popover-foreground shadow-md ring-1 ring-foreground/10 outline-none"
    ]) }}
>
    {{ $slot }}
</div>
