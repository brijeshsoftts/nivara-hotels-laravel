@props([
    "value",
])

<div
    role="tabpanel"
    data-slot="tabs-content"
    data-value="{{ $value }}"
    x-show="activeTab === '{{ $value }}'"
    x-cloak
    {{ $attributes->class(["flex-1 text-sm outline-none"]) }}
>
    {{ $slot }}
</div>
