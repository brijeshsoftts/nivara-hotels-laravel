<div
    data-slot="dialog-trigger"
    @click="open = true"
    {{ $attributes->class(["inline-block cursor-pointer"]) }}
>
    {{ $slot }}
</div>
