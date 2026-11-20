@props([
    "heading" => null,
])

<div
    data-slot="command-group"
    {{ $attributes->class(["overflow-hidden p-1 text-foreground"]) }}
>
    @if($heading)
        <div class="px-2 py-1.5 text-xs font-medium text-muted-foreground">
            {{ $heading }}
        </div>
    @endif
    {{ $slot }}
</div>
