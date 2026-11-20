@props([
    "defaultOpen" => true,
    "open" => null,
])

<div
    data-slot="sidebar-wrapper"
    x-data="{
        open: @js($open ?? $defaultOpen),
        openMobile: false,
        isMobile: window.innerWidth < 768,
        toggleSidebar() {
            if (this.isMobile) {
                this.openMobile = !this.openMobile;
            } else {
                this.open = !this.open;
                document.cookie = `sidebar_state=${this.open}; path=/; max-age=604800`;
            }
        },
        init() {
            window.addEventListener('resize', () => {
                this.isMobile = window.innerWidth < 768;
            });
            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'b') {
                    e.preventDefault();
                    this.toggleSidebar();
                }
            });
        }
    }"
    :data-state="open ? 'expanded' : 'collapsed'"
    {{ $attributes->class([
        "group/sidebar-wrapper has-data-[variant=inset]:bg-sidebar flex min-h-svh w-full",
        "[--sidebar-width:16rem] [--sidebar-width-icon:3rem] [--sidebar-width-mobile:18rem]"
    ]) }}
>
    {{ $slot }}
</div>
