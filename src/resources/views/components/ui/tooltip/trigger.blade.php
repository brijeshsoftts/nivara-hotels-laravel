<div
    data-slot="tooltip-trigger"
    @mouseenter="open = true"
    @mouseleave="open = false"
    @focus="open = true"
    @blur="open = false"
    {{ $attributes->class(["inline-block cursor-default"]) }}
>
    {{ $slot }}
</div>
