<div
    data-slot="dropdown-menu-trigger"
    @click="open = !open"
    {{ $attributes->class(["inline-block cursor-pointer"]) }}
>
    {{ $slot }}
</div>
