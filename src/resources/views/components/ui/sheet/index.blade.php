@props([
    "id" => null,
    "open" => false,
])

<div
    data-slot="sheet"
    x-data="{ open: @js($open) }"
    @if($id)
        @open-sheet.window="if ($event.detail === '{{ $id }}' || !$event.detail) open = true"
        @close-sheet.window="if ($event.detail === '{{ $id }}' || !$event.detail) open = false"
    @endif
    @keydown.escape.window="open = false"
    {{ $attributes }}
>
    {{ $slot }}
</div>
