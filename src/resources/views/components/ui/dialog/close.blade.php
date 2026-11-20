<button
    type="button"
    data-slot="dialog-close"
    @click="open = false"
    {{ $attributes }}
>
    {{ $slot }}
</button>
