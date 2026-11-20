@props([
    "name" => null,
    "value" => null,
    "placeholder" => "Select an option",
])

<div
    data-slot="select"
    x-data="{
        open: false,
        value: @js(old($name, $value)),
        label: @js($placeholder),
        select(val, text) {
            this.value = val;
            this.label = text;
            this.open = false;
            this.$dispatch('change', val);
        }
    }"
    @keydown.escape.window="open = false"
    class="relative inline-block w-full"
    {{ $attributes->whereDoesntStartWith(['class', 'name', 'value']) }}
>
    @if($name)
        <input type="hidden" name="{{ $name }}" :value="value" />
    @endif
    {{ $slot }}
</div>
