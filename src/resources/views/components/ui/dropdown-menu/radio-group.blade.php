@props([
    "value" => null,
])

<div
    role="group"
    data-slot="dropdown-menu-radio-group"
    x-data="{ selectedValue: @js($value) }"
    {{ $attributes }}
>
    {{ $slot }}
</div>
