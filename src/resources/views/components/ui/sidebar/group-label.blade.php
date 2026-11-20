<div
    data-slot="sidebar-group-label"
    {{ $attributes->class([
        "duration-200 flex h-8 shrink-0 items-center rounded-md px-2 text-xs font-medium text-sidebar-foreground/70 outline-hidden transition-[margin,opacity] ease-linear group-data-[collapsible=icon]:hidden"
    ]) }}
>
    {{ $slot }}
</div>
