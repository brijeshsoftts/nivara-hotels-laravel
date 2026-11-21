@props([
    "variant" => "default",
    "href" => null,
    "type" => "button",
])

<x-ui.button
    :variant="$variant"
    size="lg"
    :href="$href"
    :type="$type"
    {{ $attributes->class(["h-10 gap-2"]) }}
>
    {{ $slot }}
</x-ui.button>
