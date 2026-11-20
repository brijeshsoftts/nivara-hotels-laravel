<button
    type="button"
    data-slot="sidebar-group-action"
    {{ $attributes->class([
        "absolute right-3 top-3.5 flex aspect-square w-5 items-center justify-center rounded-md p-0 text-sidebar-foreground outline-hidden transition-transform hover:bg-sidebar-accent hover:text-sidebar-accent-foreground group-data-[collapsible=icon]:hidden [&>svg]:size-4"
    ]) }}
>
    {{ $slot }}
</button>
