<button
    type="button"
    data-slot="sheet-close"
    @click="open = false"
    {{ $attributes }}
>
    {{ $slot }}
</button>
