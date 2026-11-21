@props([
    "label",
    "name" => null,
    "id" => null,
    "options" => [],
    "value" => null,
    "placeholder" => "Select an option",
    "disabled" => false,
    "error" => null,
])

@php
    $selectId = $id ?? ($name ?? "select_" . uniqid());
    $currentVal = $name ? old($name, $value) : $value;
    $errorMsg = $error ?? ($name && isset($errors) && $errors->has($name) ? $errors->first($name) : null);
@endphp

<div class="space-y-2">
    <x-ui.label for="{{ $selectId }}">{{ $label }}</x-ui.label>

    <x-ui.select
        :name="$name"
        :value="$currentVal"
        :placeholder="$placeholder"
        {{ $attributes->whereDoesntStartWith(['class', 'name', 'value', 'placeholder']) }}
    >
        <x-ui.select.trigger id="{{ $selectId }}" :disabled="$disabled" class="{{ $attributes->get('class') }}">
            <x-ui.select.value :placeholder="$placeholder" />
        </x-ui.select.trigger>

        <x-ui.select.content>
            @foreach($options as $opt)
                @php
                    $optVal = is_array($opt) ? ($opt['value'] ?? '') : (is_object($opt) ? ($opt->value ?? '') : $opt);
                    $optLabel = is_array($opt) ? ($opt['label'] ?? $optVal) : (is_object($opt) ? ($opt->label ?? $optVal) : $opt);
                    $optLabel = ucwords(str_replace('_', ' ', strtolower((string)$optLabel)));
                @endphp
                <x-ui.select.item :value="$optVal">
                    {{ $optLabel }}
                </x-ui.select.item>
            @endforeach
        </x-ui.select.content>
    </x-ui.select>

    @if($errorMsg)
        <span class="text-sm text-destructive">
            {{ $errorMsg }}
        </span>
    @endif
</div>
