@props([
    "id" => null,
    "open" => false,
])

<div
    data-slot="dialog"
    x-data="{ open: @js($open) }"
    @if($id)
        @open-dialog.window="if ($event.detail === '{{ $id }}' || !$event.detail) open = true"
        @close-dialog.window="if ($event.detail === '{{ $id }}' || !$event.detail) open = false"
    @endif
    @keydown.escape.window="open = false"
    {{ $attributes }}
>
    {{ $slot }}
</div>
