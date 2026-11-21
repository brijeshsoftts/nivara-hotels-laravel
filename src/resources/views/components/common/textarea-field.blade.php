@props([
    "label",
    "name" => null,
    "id" => null,
    "placeholder" => null,
    "error" => null,
    "disabled" => false,
    "required" => false,
    "value" => null,
])

@php
    $inputId = $id ?? ($name ?? "textarea_" . uniqid());
    $errorMsg = $error ?? ($name && isset($errors) && $errors->has($name) ? $errors->first($name) : null);
    $val = $name ? old($name, $value) : $value;
@endphp

<div class="space-y-2">
    <div class="space-y-2">
        <x-ui.label for="{{ $inputId }}">{{ $label }}</x-ui.label>

        <x-ui.textarea
            id="{{ $inputId }}"
            name="{{ $name }}"
            :required="$required"
            :placeholder="$placeholder"
            :disabled="$disabled"
            :aria-invalid="$errorMsg ? 'true' : 'false'"
            :aria-describedby="$errorMsg ? $inputId . '-error' : null"
            {{ $attributes->whereDoesntStartWith(['class', 'id', 'name', 'placeholder', 'required', 'disabled'])->class(['h-20 resize-none']) }}
        >{{ $slot->isNotEmpty() ? $slot : $val }}</x-ui.textarea>
    </div>

    @if($errorMsg)
        <span id="{{ $inputId }}-error" class="text-sm text-destructive">
            {{ $errorMsg }}
        </span>
    @endif
</div>
