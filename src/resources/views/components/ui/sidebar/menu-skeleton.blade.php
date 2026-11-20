@props([
    "showIcon" => false,
])

<div
    data-slot="sidebar-menu-skeleton"
    {{ $attributes->class(["flex h-8 items-center gap-2 rounded-md px-2"]) }}
>
    @if($showIcon)
        <div class="size-4 animate-pulse rounded-md bg-sidebar-accent"></div>
    @endif
    <div class="h-4 flex-1 animate-pulse rounded-md bg-sidebar-accent"></div>
</div>
