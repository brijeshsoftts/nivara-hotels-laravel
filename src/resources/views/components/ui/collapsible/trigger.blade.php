<button
    type="button"
    data-slot="collapsible-trigger"
    @click="open = !open"
    :aria-expanded="open ? 'true' : 'false'"
    {{ $attributes }}
>
    {{ $slot }}
</button>
