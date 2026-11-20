<div
    data-slot="popover-trigger"
    @click="open = !open"
    {{ $attributes->class(["inline-block cursor-pointer"]) }}
>
    {{ $slot }}
</div>
