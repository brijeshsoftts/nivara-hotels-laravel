<button
    type="button"
    data-slot="sidebar-rail"
    @click="toggleSidebar"
    aria-label="Toggle Sidebar"
    tabindex="-1"
    {{ $attributes->class([
        "absolute inset-y-0 z-20 hidden w-4 -translate-x-1/2 transition-all ease-linear after:absolute after:inset-y-0 after:left-1/2 after:w-[2px] hover:after:bg-sidebar-border group-data-[side=left]:-right-4 group-data-[side=right]:left-0 sm:flex"
    ]) }}
></button>
