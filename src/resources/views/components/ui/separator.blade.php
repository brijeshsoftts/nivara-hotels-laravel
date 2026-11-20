@props([
    "orientation" => "horizontal",
])

<div
    role="separator"
    data-slot="separator"
    data-orientation="{{ $orientation }}"
    {{ $attributes->class([
        "shrink-0 bg-border",
        "h-px w-full data-horizontal:h-px data-horizontal:w-full" => $orientation === "horizontal",
        "w-px self-stretch data-vertical:w-px data-vertical:self-stretch" => $orientation === "vertical",
    ]) }}
></div>
