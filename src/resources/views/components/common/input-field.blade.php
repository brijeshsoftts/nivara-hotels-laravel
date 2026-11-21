@props([
    "label",
    "name" => null,
    "id" => null,
    "type" => "text",
    "placeholder" => null,
    "autoComplete" => null,
    "error" => null,
    "disabled" => false,
    "required" => false,
    "value" => null,
])

@php
    $inputId = $id ?? ($name ?? "input_" . uniqid());
    $errorMsg = $error ?? ($name && isset($errors) && $errors->has($name) ? $errors->first($name) : null);
    $val = $name ? old($name, $value) : $value;
@endphp

<div class="space-y-2">
    <div class="space-y-2">
        <x-ui.label for="{{ $inputId }}">{{ $label }}</x-ui.label>

        <x-ui.input
            id="{{ $inputId }}"
            name="{{ $name }}"
            type="{{ $type }}"
            :required="$required"
            :placeholder="$placeholder"
            :autocomplete="$autoComplete"
            :disabled="$disabled"
            :value="$val"
            :aria-invalid="$errorMsg ? 'true' : 'false'"
            :aria-describedby="$errorMsg ? $inputId . '-error' : null"
            {{ $attributes->whereDoesntStartWith(['class', 'id', 'name', 'type', 'placeholder', 'required', 'disabled', 'value']) }}
            class="{{ $attributes->get('class') }}"
        />
    </div>

    @if($errorMsg)
        <span id="{{ $inputId }}-error" class="text-sm text-destructive">
            {{ $errorMsg }}
        </span>
    @endif
</div>
