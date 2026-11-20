@props([
    "name",
    "class" => "size-4",
])

@php
    $icons = [
        "chevron-down" => '<path d="m6 9 6 6 6-6"/>',
        "chevron-up" => '<path d="m18 15-6-6-6 6"/>',
        "chevron-right" => '<path d="m9 18 6-6-6-6"/>',
        "chevron-left" => '<path d="m15 18-6-6 6-6"/>',
        "check" => '<path d="M20 6 9 17l-5-5"/>',
        "x" => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        "more-horizontal" => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
        "loader-2" => '<path d="M21 12a9 9 0 1 1-6.219-8.56"/>',
        "circle-check" => '<circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/>',
        "info" => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        "triangle-alert" => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" x2="12" y1="9" y2="13"/><line x1="12" x2="12.01" y1="17" y2="17"/>',
        "octagon-x" => '<polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"/><line x1="15" x2="9" y1="9" y2="15"/><line x1="9" x2="15" y1="9" y2="15"/>',
        "panel-left" => '<rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>',
        "search" => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    ];
@endphp

<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    {{ $attributes->class([$class]) }}
>
    {!! $icons[$name] ?? "" !!}
</svg>
