<div
    data-slot="sidebar-menu-badge"
    {{ $attributes->class([
        "pointer-events-none absolute right-1 flex h-5 min-w-5 select-none items-center justify-center rounded-md px-1 text-xs font-medium tabular-nums text-sidebar-foreground group-data-[collapsible=icon]:hidden"
    ]) }}
>
    {{ $slot }}
</div>
