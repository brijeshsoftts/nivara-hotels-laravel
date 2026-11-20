@props([
    "side" => "left",
    "variant" => "sidebar",
    "collapsible" => "offcanvas",
])

<aside
    data-slot="sidebar"
    data-side="{{ $side }}"
    data-variant="{{ $variant }}"
    data-collapsible="{{ $collapsible }}"
    :data-state="open ? 'expanded' : 'collapsed'"
    {{ $attributes->class([
        "group peer hidden md:block text-sidebar-foreground transition-[width] ease-linear duration-200",
        "w-(--sidebar-width) data-[state=collapsed]:w-(--sidebar-width-icon)" => $collapsible === "icon",
        "w-(--sidebar-width) data-[state=collapsed]:w-0 data-[state=collapsed]:overflow-hidden" => $collapsible === "offcanvas",
    ]) }}
>
    <div
        class="flex h-full w-(--sidebar-width) flex-col bg-sidebar border-r border-sidebar-border"
    >
        {{ $slot }}
    </div>
</aside>

<!-- Mobile Sidebar Sheet -->
<template x-teleport="body">
    <div
        x-show="openMobile"
        x-cloak
        class="fixed inset-0 z-50 md:hidden"
    >
        <div
            x-show="openMobile"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="openMobile = false"
            class="fixed inset-0 bg-black/50 backdrop-blur-xs"
        ></div>
        <div
            x-show="openMobile"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 flex h-full w-(--sidebar-width-mobile) max-w-[80vw] flex-col bg-sidebar p-2 text-sidebar-foreground shadow-lg"
        >
            {{ $slot }}
        </div>
    </div>
</template>
