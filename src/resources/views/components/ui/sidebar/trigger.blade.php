<button
    type="button"
    data-slot="sidebar-trigger"
    @click="toggleSidebar"
    {{ $attributes->class([
        "inline-flex size-7 items-center justify-center rounded-md text-foreground hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring outline-none"
    ]) }}
>
    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>
    </svg>
    <span class="sr-only">Toggle Sidebar</span>
</button>
