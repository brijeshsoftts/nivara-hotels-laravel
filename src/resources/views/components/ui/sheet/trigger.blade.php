<div
    data-slot="sheet-trigger"
    @click="open = true"
    {{ $attributes->class(["inline-block cursor-pointer"]) }}
>
    {{ $slot }}
</div>
