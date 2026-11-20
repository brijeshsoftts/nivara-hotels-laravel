<button
    type="button"
    data-slot="sidebar-menu-action"
    {{ $attributes->class([
        "absolute right-1 top-1.5 flex aspect-square w-5 items-center justify-center rounded-md p-0 text-sidebar-foreground outline-hidden transition-transform hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0 peer-hover/menu-button:text-sidebar-accent-foreground group-data-[collapsible=icon]:hidden"
    ]) }}
>
    {{ $slot }}
</button>
