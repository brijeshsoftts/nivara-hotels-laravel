@props([
    "filterKey",
    "label",
    "options" => [],
    "initialValue" => "",
    "placeholder" => null,
    "disabled" => false,
])

<x-ui.select
    :name="$filterKey"
    :value="$initialValue"
    :placeholder="$placeholder ?? 'All ' . $label"
    {{ $attributes }}
>
    <x-ui.select.trigger :disabled="$disabled" class="{{ $attributes->get('class') }}">
        <x-ui.select.value :placeholder="$placeholder ?? 'All ' . $label" />
    </x-ui.select.trigger>

    <x-ui.select.content>
        <x-ui.select.item value="">All {{ $label }}</x-ui.select.item>
        @foreach($options as $opt)
            @php
                $val = is_array($opt) ? ($opt['value'] ?? '') : $opt;
                $text = is_array($opt) ? ($opt['label'] ?? $val) : $opt;
            @endphp
            <x-ui.select.item :value="$val">
                {{ $text }}
            </x-ui.select.item>
        @endforeach
    </x-ui.select.content>
</x-ui.select>
